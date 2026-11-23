<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use UsuariosFuncionalidad\Aplicacion\DTOs\RegistrarUsuarioEntradaDTO;
use UsuariosFuncionalidad\Dominio\Entidades\TokenConfirmacionUsuarioEntidad;
use UsuariosFuncionalidad\Dominio\Repositorios\TokenConfirmacionUsuarioRepositorioInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;

class GenerarTokenConfirmacionUsuarioCasoDeUso
{
    public function __construct(
        private TokenConfirmacionUsuarioRepositorioInterface $mTokenConfirmacionUsuarioRepositorio,
        private CatalogoMensajesInterface $mensajes
    ) {}

    /**
     * @param int $esta_verificado_usuario
     * @return void
     * @throws Exception Si se violan reglas de negocio o validaciones básicas.
     */
    public function execute(int $id_usuario, bool $esta_verificado_usuario): string
    {
        // $esta_verificado_usuario = 0 (no verificado)
        // $esta_verificado_usuario = 1 (verificado) Genera Excpcion
        if ($esta_verificado_usuario) {
            throw ReglaNegocioExcepcion::usuarioYaVerificado();
        }

        // 1. Generación de un token criptográfico seguro de 64 caracteres de forma nativa
        $token_verificacion = bin2hex(random_bytes(32));

        // 2. Configurar la fecha de expiración (ej: +24 horas a partir de este instante)
        $fecha_actual = new \DateTime();
        $fecha_actual->modify('+24 hours');
        $fecha_expiracion = $fecha_actual->format('Y-m-d H:i:s');

        $mTokenConfirmacionUsuarioEntidad = new TokenConfirmacionUsuarioEntidad(
            id_token: null,
            id_usuario: $id_usuario,
            token_verificacion: $token_verificacion,
            fecha_expiracion: $fecha_expiracion,
        );

        // 3. Persistencia: Solicitamos al repositorio guardar la entidad estructurada
        $this->mTokenConfirmacionUsuarioRepositorio->crearTokenVerificacion($mTokenConfirmacionUsuarioEntidad);

        return $token_verificacion;

    }

}