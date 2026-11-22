<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use UsuariosFuncionalidad\Dominio\Repositorios\TokenConfirmacionUsuarioRepositorioInterface;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use Exception;

class VerificacionTokenConfirmacionUsuarioCasoDeUso
{
    public function __construct(
        private TokenConfirmacionUsuarioRepositorioInterface $mTokenConfirmacionUsuarioRepositorioImp,
        private UsuarioRepositorioInterface $mUsuarioRepositorioImp
    ) {}

    /**
     * @param string $token_verificacion
     * @return void
     * @throws Exception Si el token es inválido o expiró.
     */
    public function execute(string $token_verificacion): void
    {

        $token_verificacion_clean = trim($token_verificacion);
                
        // 1. Regla de Negocio: Buscar el token en la base de datos
        // (Para esto, expandirías tu interfaz de repositorio con este método)
        $token_verificacion_data = $this->mTokenConfirmacionUsuarioRepositorioImp->encontrarPorToken($token_verificacion_clean);

        // I N I   T E M P O R A L
        //throw new Exception("DEBUG -> Token recibido: [" . $token_verificacion_clean . "] | Resultado BD: " . json_encode($token_verificacion_data));
        // F I N   T E M P O R A L
        
        if (!$token_verificacion_data) {
            throw new Exception("El enlace de verificación no es válido o ya fue utilizado.");
        }

        // 2. Regla de Negocio: Validar que el token no haya expirado
        $now = new \DateTime();
        $expiration = new \DateTime($token_verificacion_data['fecha_expiracion']);

        if ($now > $expiration) {
            throw new Exception("El enlace de verificación ha expirado. Solicite uno nuevo.");
        }

        // 3. Acción de Negocio: Marcar al usuario como verificado y borrar el token usado
        $id_usuario = $token_verificacion_data['fk_id_usuario'];
        
        $this->mUsuarioRepositorioImp->marcarComoVerificado($id_usuario);
        $this->mTokenConfirmacionUsuarioRepositorioImp->borrarToken($token_verificacion_clean);
    }
}
