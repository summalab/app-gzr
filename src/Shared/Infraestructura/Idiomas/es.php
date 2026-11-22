<?php

declare(strict_types=1);

return [
    'sistema' => [
        'error_interno'        => 'Ocurrió un error interno en el servidor. Inténtalo más tarde.',
        'no_autorizado'        => 'Debes iniciar sesión para realizar esta acción.',
        'demasiadas_peticiones'=> 'Has realizado demasiadas solicitudes. Intenta nuevamente en {segundos} segundos.',
    ],
    'ubicacion' => [
        'titulo_invalido'      => 'El título debe tener al menos {minimo} caracteres y palabras descriptivas reales.',
        'descripcion_corta'    => 'La descripción es obligatoria y debe explicar brevemente el lugar (mínimo {minimo} caracteres).',
        'fuera_de_zona'        => 'Ubicación fuera de zona: el punto debe estar dentro de un radio de {radio} km alrededor de Pereira.',
        'duplicada_cercana'    => 'Ya existe una ubicación registrada exactamente en este punto (a menos de {metros} metros).',
        'creada_exito'         => 'Ubicación guardada correctamente.',
        'eliminada_exito'      => 'Ubicación eliminada correctamente.',
        'no_encontrada'        => 'La ubicación solicitada no existe o no tienes permiso para modificarla.',
    ],
    'usuario' => [
        'correo_invalido'      => 'El formato del correo electrónico no es válido.',
        'correo_duplicado'     => 'El correo electrónico ya se encuentra registrado en el sistema.',
        'apodo_duplicado'      => 'El apodo elegido ya está en uso por otro usuario.',
        'contrasena_corta'     => 'La contraseña debe tener al menos {minimo} caracteres.',
        'credenciales_malas'   => 'Correo electrónico o contraseña incorrectos.',
        'cuenta_no_verificada' => 'Debes verificar tu correo electrónico antes de iniciar sesión.',
    ],
];
