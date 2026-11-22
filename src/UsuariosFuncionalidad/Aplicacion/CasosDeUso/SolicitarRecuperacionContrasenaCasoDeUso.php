<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use DateTime;
use InvalidArgumentException;
use Throwable;
use UsuariosFuncionalidad\Dominio\Entidades\TokenConfirmacionUsuarioEntidad;
use UsuariosFuncionalidad\Dominio\Repositorios\TokenConfirmacionUsuarioRepositorioInterface;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use UsuariosFuncionalidad\Dominio\Servicios\EnviadorCorreoInterface;

class SolicitarRecuperacionContrasenaCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private TokenConfirmacionUsuarioRepositorioInterface $mTokenRepositorio,
        private EnviadorCorreoInterface $mEnviadorCorreo
    ) {}

    public function execute(string $correo): void
    {
        $correoLimpio = trim($correo);

        if ($correoLimpio === '' || !filter_var($correoLimpio, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El formato del correo electrónico no es válido.');
        }

        try {
            // 1. Buscar al usuario por su correo electrónico
            $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorEmail($correoLimpio);

            // Seguridad anti-enumeración: Si el correo no existe, salimos en silencio
            // sin lanzar error para que el controlador siempre responda con éxito genérico.
            if (!$mUsuarioEntidad) {
                return;
            }

            // 2. Generar un token criptográfico seguro de 64 caracteres
            $tokenRecuperacion = bin2hex(random_bytes(32));

            // 3. Configurar expiración corta de 1 hora
            $fechaExpiracion = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

            // 4. Construir la entidad de token especificando tipo_token = 'RECUPERACION'
            $mTokenEntidad = new TokenConfirmacionUsuarioEntidad(
                id_token: null,
                id_usuario: $mUsuarioEntidad->obtenerId(),
                token_verificacion: $tokenRecuperacion,
                fecha_expiracion: $fechaExpiracion,
                tipo_token: 'RECUPERACION'
            );

            // 5. Guardar el token en base de datos
            $this->mTokenRepositorio->crearTokenVerificacion($mTokenEntidad);

            // 6. Armar el enlace dinámico (?recuperar=TOKEN) y enviar correo
            $urlBase = rtrim(\Core\EnvLoader::obtener('APP_URL', 'http://app-gzr.test'), '/');
            $enlaceRecuperacion = "{$urlBase}/?recuperar={$tokenRecuperacion}";

            $this->mEnviadorCorreo->enviarCorreoRecuperacion(
                $mUsuarioEntidad->obtenerCorreo(),
                $mUsuarioEntidad->obtenerApodo(),
                $enlaceRecuperacion
            );

        } catch (InvalidArgumentException $reglaExcepcion) {
            throw $reglaExcepcion;
        } catch (Throwable $falla) {
            error_log('Error crítico en SolicitarRecuperacionContrasenaCasoDeUso: ' . $falla->getMessage());
            throw new \Exception('No se pudo procesar la solicitud de recuperación por un problema interno.');
        }
    }
}
