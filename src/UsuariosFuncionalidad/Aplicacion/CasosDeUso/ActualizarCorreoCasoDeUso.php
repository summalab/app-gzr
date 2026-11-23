<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Throwable;
use UsuariosFuncionalidad\Aplicacion\DTOs\ActualizarCorreoEntradaDTO;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use UsuariosFuncionalidad\Dominio\Servicios\EnviadorCorreoInterface;
use Shared\Dominio\Servicios\AutorizadorInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use Shared\Dominio\Servicios\GestorTransaccionesInterface;

class ActualizarCorreoCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private GenerarTokenConfirmacionUsuarioCasoDeUso $mGenerarTokenConfirmacionUsuarioCasoDeUso,
        private EnviadorCorreoInterface $mEnviadorCorreoInterfaceImp,
        private AutorizadorInterface $mAutorizador,
        private CatalogoMensajesInterface $mensajes,
        private GestorTransaccionesInterface $transacciones
    ) {}

    public function execute(ActualizarCorreoEntradaDTO $dto): void
    {
        $this->mAutorizador->exigirPermiso('perfil.gestionar_propio');
        $nuevoCorreoLimpio = trim($dto->nuevo_correo);

        if ($nuevoCorreoLimpio === '' || !filter_var($nuevoCorreoLimpio, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException($this->mensajes->obtener('usuario.correo_nuevo_invalido'));
        }

        // 1. Recuperar al usuario actual desde la base de datos
        $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorId($dto->id_usuario);

        if (!$mUsuarioEntidad) {
            throw new ReglaNegocioExcepcion($this->mensajes->obtener('usuario.usuario_no_encontrado'), 404);
        }

        // 2. Si el correo nuevo es idéntico al actual, no hacemos nada
        if (strtolower($mUsuarioEntidad->obtenerCorreo()) === strtolower($nuevoCorreoLimpio)) {
            return;
        }

        // 3. Validar que el nuevo correo no esté ocupado por otro usuario
        if ($this->mUsuarioRepositorio->encontrarPorEmail($nuevoCorreoLimpio)) {
            throw ReglaNegocioExcepcion::correoDuplicado($nuevoCorreoLimpio);
        }

        // 4. Iniciar transacción ACID para actualizar usuario y generar nuevo token
        $tokenVerificacion = $this->transacciones->ejecutar(function () use ($mUsuarioEntidad, $nuevoCorreoLimpio) {
            // Mutamos la entidad (esto cambia el correo y pone esta_verificado_usuario = false)
            $mUsuarioEntidad->actualizarCorreo($nuevoCorreoLimpio);

            // Guardamos el cambio en MySQL (ejecuta UPDATE porque ya tiene ID)
            $this->mUsuarioRepositorio->guardar($mUsuarioEntidad);

            // Generamos el nuevo token de verificación
            $tokenVerificacion = $this->mGenerarTokenConfirmacionUsuarioCasoDeUso->execute(
                $mUsuarioEntidad->obtenerId(),
                $mUsuarioEntidad->obtenerVerificacionUsuario()
            );

            return $tokenVerificacion;
        });

        $urlBase = rtrim(\Core\EnvLoader::obtener('APP_URL', 'http://app-gzr.test'), '/');
        $enlaceVerificacion = "{$urlBase}/?token={$tokenVerificacion}";
        $this->mEnviadorCorreoInterfaceImp->enviarCorreoVerificacion(
            $mUsuarioEntidad->obtenerCorreo(),
            $mUsuarioEntidad->obtenerApodo(),
            $enlaceVerificacion
        );
    }
}
