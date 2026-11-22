<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use UsuariosFuncionalidad\Aplicacion\DTOs\RegistrarUsuarioEntradaDTO;
use UsuariosFuncionalidad\Dominio\Entidades\UsuarioEntidad;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use UsuariosFuncionalidad\Dominio\Servicios\EnviadorCorreoInterface;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;

// Este namespace es nativo de PHP
use InvalidArgumentException;

class RegistrarUsuarioCasoDeUso
{
    /**
     * Inyectamos el contrato del repositorio, cumpliendo con la Inversión de Dependencias.
     * No sabemos ni nos importa si guarda en MySQL, PostgreSQL o memoria.
     */
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private GenerarTokenConfirmacionUsuarioCasoDeUso $mGenerarTokenConfirmacionUsuarioCasoDeUso,
        private EnviadorCorreoInterface $mEnviadorCorreoInterfaceImp,
        private GestorTransaccionesInterface $transacciones
    ) {}

    /**
     * Coordina el flujo completo para registrar un nuevo usuario en el sistema.
     * 
     * @param RegistrarUsuarioEntradaDTO $mUsuarioDto
     * @return void
     * @throws \Exception Si se violan reglas de negocio o validaciones básicas.
     */
    public function execute(RegistrarUsuarioEntradaDTO $mUsuarioDto): void
    {

        //---------------------------------------------------------------------

        try {

            // 2. Escritura atómica ACID en múltiples tablas
            $token_verificacion = $this->transacciones->ejecutar(function () use ($mUsuarioDto) {

                // 1. Registrar el usuario en base de datos
                $datos_usuario_reg = $this->registrarUsuario($mUsuarioDto);

                // 2. Generar el token de verificación
                return $this->generarTokenConfirmacion(
                    $datos_usuario_reg['id_usuario'],
                    $datos_usuario_reg['esta_verificado_usuario']
                );
            });

            // 4. Armar URL dinámica desde .env y enviar correo fuera de la transacción
            $urlBase = rtrim(\Core\EnvLoader::obtener('APP_URL', 'http://app-gzr.test'), '/');
            $enlaceVerificacion = "{$urlBase}/?token={$token_verificacion}";

            // 3. Envío de correo SMTP fuera de la transacción (la red nunca debe mantener abierta una transacción BD)
            $this->mEnviadorCorreoInterfaceImp->enviarCorreoVerificacion(
                $datos_usuario_reg['correo_usuario'],
                $datos_usuario_reg['apodo_usuario'],
                $enlaceVerificacion
            );

        } catch (ReglaNegocioExcepcion | InvalidArgumentException $reglaExcepcion) {
            $this->mUsuarioRepositorio->rollBack();
            // Relanzamos intacta la excepción de negocio para que el controlador responda 422
            throw $reglaExcepcion;

        } catch (\Throwable $falla) {

            // ¡EL SALVAVIDAS! Si la operación 1 o la operación 2 fallaron, 
            // MySQL borra todo rastro del usuario recién creado. Cero basura, cero inconsistencias.
            $this->mUsuarioRepositorio->rollBack();
            // Registramos el fallo técnico en el log privado y lanzamos error genérico
            error_log("Error crítico en RegistrarUsuarioCasoDeUso: " . $falla->getMessage());
            throw new \Exception("No se pudo completar el registro por un problema interno.");
        }
    }

    private function registrarUsuario(RegistrarUsuarioEntradaDTO $mUsuarioDto): array
    {
        
    // funcion trim() eliminar todos los espacios en blanco (u otros caracteres invisibles)
        // que estén al principio y al final de una cadena de texto pero NO altera los 
        // espacios que se encuentran en medio de las palabras.
        $apodo_usuario_clean  = trim($mUsuarioDto->apodo_usuario);
        $correo_usuario_clean = trim($mUsuarioDto->correo_usuario);

        // Regla de Negocio: Validar duplicidad de apodos
        if ($this->mUsuarioRepositorio->encontrarPorApodo($apodo_usuario_clean)) {
            throw ReglaNegocioExcepcion::apodoDuplicado($apodo_usuario_clean);
        }

        // Regla de Negocio: Validar duplicidad de correos electrónicos
        if ($this->mUsuarioRepositorio->encontrarPorEmail($correo_usuario_clean)) {
            throw ReglaNegocioExcepcion::correoDuplicado($correo_usuario_clean);
        }

        // Validación de Entrada: Forzar una longitud mínima de contraseña segura
        if (strlen($mUsuarioDto->contrasena_hash_usuario) < 8) {
            throw ReglaNegocioExcepcion::contrasenaCorta(8);
        }

        // Cifrado Seguro: Hashear la contraseña usando la configuración por defecto (Bcrypt $2y$10$)
        // PASSWORD_DEFAULT es adaptativo y evoluciona con las mejoras de PHP sin romper tu código.
        $contrasena_hash_usuario = password_hash($mUsuarioDto->contrasena_hash_usuario, PASSWORD_DEFAULT);

        // 4. Mapeo al Dominio: Construimos la entidad pura con los datos validados y cifrados.
        // El ID viaja como nulo porque aún no ha sido persistido en la base de datos.
        $mUsuarioEntidad = new UsuarioEntidad(
            id_usuario: null,
            apodo_usuario: $apodo_usuario_clean,
            correo_usuario: $correo_usuario_clean,
            contrasena_hash_usuario: $contrasena_hash_usuario,
            esta_verificado_usuario: false //El FALSE es OBLIGATORIO al crear nuevo usuario, indica que cuenta nace sin verificacion
        );

        // 5. Persistencia: Solicitamos al repositorio guardar la entidad estructurada
        $mUsuarioEntidad = $this->mUsuarioRepositorio->guardar($mUsuarioEntidad);

        return [
            'id_usuario'              => $mUsuarioEntidad->obtenerId(),
            'esta_verificado_usuario' => $mUsuarioEntidad->obtenerVerificacionUsuario(),
            'apodo_usuario'           => $mUsuarioEntidad->obtenerApodo(),
            'correo_usuario'          => $mUsuarioEntidad->obtenerCorreo(),
        ];
    }

    private function generarTokenConfirmacion(int $id_usuario, bool $esta_verificado_usuario): string
    {
        // Operación 2: Invocamos el segundo caso de uso pasando el ID
        // Si esto falla (ej: error en la tabla de tokens), saltará de inmediato al catch
        //$token = $this->mGenerarTokenConfirmacionUsuarioCasoDeUso->execute($id_usuario, $esta_verificado_usuario);
        return $this->mGenerarTokenConfirmacionUsuarioCasoDeUso->execute($id_usuario, $esta_verificado_usuario);
    }
}
