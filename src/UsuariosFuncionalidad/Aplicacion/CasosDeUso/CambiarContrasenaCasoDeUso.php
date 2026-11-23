<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Throwable;
use UsuariosFuncionalidad\Aplicacion\DTOs\CambiarContrasenaEntradaDTO;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use Shared\Dominio\Servicios\AutorizadorInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use Shared\Dominio\Servicios\GestorTransaccionesInterface;

class CambiarContrasenaCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private AutorizadorInterface $mAutorizador,
        private CatalogoMensajesInterface $mensajes,
        private GestorTransaccionesInterface $transacciones
    ) {}

    public function execute(CambiarContrasenaEntradaDTO $dto): void
    {
        $this->mAutorizador->exigirPermiso('perfil.gestionar_propio');
        // 1. Recuperar al usuario actual desde el repositorio
        $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorId($dto->id_usuario);

        if (!$mUsuarioEntidad) {
            throw new ReglaNegocioExcepcion($this->mensajes->obtener('usuario.usuario_no_encontrado'), 404);
        }

        // 2. Verificar que la contraseña actual sea correcta usando password_verify
        if (!password_verify($dto->contrasena_actual, $mUsuarioEntidad->obtenerContrasenadHash())) {
            throw new ReglaNegocioExcepcion($this->mensajes->obtener('usuario.contrasena_actual_mala'), 422);
        }

        // 3. Validar la longitud mínima de la nueva contraseña
        if (strlen($dto->contrasena_nueva) < 8) {
            throw ReglaNegocioExcepcion::contrasenaCorta(8);
        }

        // 4. Evitar que ponga exactamente la misma contraseña que ya tiene
        if (password_verify($dto->contrasena_nueva, $mUsuarioEntidad->obtenerContrasenadHash())) {
            throw new ReglaNegocioExcepcion($this->mensajes->obtener('usuario.contrasena_diferente'), 422);
        }

        try {
            // 5. Generar el nuevo hash seguro con Bcrypt / Argon2 (PASSWORD_DEFAULT)
            $nuevoHashContrasena = password_hash($dto->contrasena_nueva, PASSWORD_DEFAULT);

            // 6. Mutar el estado de la entidad (ejecuta autoValidarEntidad internamente)
            $mUsuarioEntidad->cambiarContrasena($nuevoHashContrasena);

            // 7. Persistir los cambios reutilizando el método único guardar() (ejecuta UPDATE)
            $this->transacciones->ejecutar(function () use ($mUsuarioEntidad) {
                $this->mUsuarioRepositorio->guardar($mUsuarioEntidad);
            });

        } catch (ReglaNegocioExcepcion | InvalidArgumentException $reglaExcepcion) {
            throw $reglaExcepcion;
        } catch (Throwable $falla) {
            error_log('Error crítico en CambiarContrasenaCasoDeUso: ' . $falla->getMessage());
            throw new \Exception($this->mensajes->obtener('sistema.operacion_cambio_clave'));
        }
    }
}
