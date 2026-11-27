# 03. API, reglas y persistencia

## 1. Catálogo de endpoints

Los endpoints se registran por módulo; el Router compara método HTTP y ruta normalizada exactos ([Router](../core/Router.php#L46-L94)). Los controladores responden JSON y verifican el método esperado.

| Método y ruta | Acción | Entrada principal | Sesión / permiso | Referencias |
|---|---|---|---|---|
| `POST /login` | Iniciar sesión. | `correo_usuario`, `contrasena` | Público; cuenta debe estar verificada. | [Ruta](../src/AuthFuncionalidad/AuthModulo.php#L34-L39), [controlador](../src/AuthFuncionalidad/Http/AuthControlador.php#L28-L67) |
| `POST /logout` | Destruir sesión. | Ninguna | Opera sobre la sesión activa. | [ruta](../src/AuthFuncionalidad/AuthModulo.php#L34-L39), [controlador](../src/AuthFuncionalidad/Http/AuthControlador.php#L69-L91) |
| `GET /verificar-sesion` | Devolver autenticación y usuario activo. | Ninguna | Público; sesión ausente retorna no autenticado. | [ruta](../src/AuthFuncionalidad/AuthModulo.php#L34-L39), [caso de uso](../src/AuthFuncionalidad/Aplicacion/CasosDeUso/VerificarSesionCasoDeUso.php#L25-L67) |
| `POST /registrar-usuario` | Crear cuenta no verificada y enviar token. | `apodo_usuario`, `correo_usuario`, `contrasena_hash_usuario` | Público. | [ruta](../src/UsuariosFuncionalidad/UsuariosModulo.php#L48-L54), [controlador](../src/UsuariosFuncionalidad/Http/UsuarioControlador.php#L43-L71) |
| `GET /verificar-correo` | Activar cuenta y consumir token. | query `token` | Público. | [ruta](../src/UsuariosFuncionalidad/UsuariosModulo.php#L48-L54), [controlador](../src/UsuariosFuncionalidad/Http/UsuarioControlador.php#L74-L99) |
| `POST /solicitar-recuperacion` | Generar enlace de recuperación. | `correo_usuario` | Público; respuesta genérica. | [ruta](../src/UsuariosFuncionalidad/UsuariosModulo.php#L48-L54), [controlador](../src/UsuariosFuncionalidad/Http/UsuarioControlador.php#L206-L232) |
| `POST /restablecer-contrasena` | Reemplazar clave consumiendo token. | `token`, `nueva_contrasena` | Público con token válido. | [ruta](../src/UsuariosFuncionalidad/UsuariosModulo.php#L48-L54), [controlador](../src/UsuariosFuncionalidad/Http/UsuarioControlador.php#L234-L260) |
| `POST /actualizar-apodo` | Cambiar apodo de la sesión. | `nuevo_apodo` | Sesión + `perfil.gestionar_propio`. | [ruta](../src/UsuariosFuncionalidad/UsuariosModulo.php#L56-L60), [caso de uso](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/ActualizarApodoCasoDeUso.php#L23-L62) |
| `POST /actualizar-correo` | Cambiar correo y exigir verificación nueva. | `nuevo_correo` | Sesión + `perfil.gestionar_propio`. | [ruta](../src/UsuariosFuncionalidad/UsuariosModulo.php#L56-L60), [caso de uso](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/ActualizarCorreoCasoDeUso.php#L28-L79) |
| `POST /cambiar-contrasena` | Cambiar clave con comprobación de la actual. | `contrasena_actual`, `contrasena_nueva` | Sesión + `perfil.gestionar_propio`. | [ruta](../src/UsuariosFuncionalidad/UsuariosModulo.php#L56-L60), [caso de uso](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/CambiarContrasenaCasoDeUso.php#L25-L61) |
| `POST /eliminar-cuenta` | Baja lógica de cuenta. | `contrasena_confirmacion` | Sesión + `perfil.gestionar_propio`; exige contraseña. | [ruta](../src/UsuariosFuncionalidad/UsuariosModulo.php#L56-L60), [caso de uso](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/EliminarCuentaUsuarioCasoDeUso.php#L26-L55) |
| `GET /ubicaciones-publicas` | Listar ubicación de la comunidad y configuración de geocerca. | Ninguna | Público. | [ruta](../src/UbicacionesFuncionalidad/UbicacionesModulo.php#L24-L32), [controlador](../src/UbicacionesFuncionalidad/Http/UbicacionControlador.php#L33-L58) |
| `GET /mis-ubicaciones` | Listar ubicaciones activas propias. | Ninguna | Sesión + `perfil.gestionar_propio`. | [ruta](../src/UbicacionesFuncionalidad/UbicacionesModulo.php#L24-L32), [caso de uso](../src/UbicacionesFuncionalidad/Aplicacion/CasosDeUso/ListarUbicacionesUsuarioCasoDeUso.php#L21-L44) |
| `POST /crear-ubicacion` | Crear ubicación. | `titulo`, `descripcion`, `latitud`, `longitud` | Sesión + `ubicacion.crear`. | [ruta](../src/UbicacionesFuncionalidad/UbicacionesModulo.php#L24-L32), [controlador](../src/UbicacionesFuncionalidad/Http/UbicacionControlador.php#L87-L133) |
| `POST /eliminar-ubicacion` | Baja lógica de ubicación. | `id_ubicacion` | Sesión; propia o permiso para eliminar cualquiera. | [controlador](../src/UbicacionesFuncionalidad/Http/UbicacionControlador.php#L136-L174), [caso de uso](../src/UbicacionesFuncionalidad/Aplicacion/CasosDeUso/EliminarUbicacionCasoDeUso.php#L22-L45) |

## 2. Roles y autorización

Los roles válidos definidos son `usuario`, `moderador` y `administrador` ([enum](../src/Shared/Dominio/Seguridad/RolUsuario.php#L8-L49)).

| Rol | Permisos declarados en `RolUsuario` |
|---|---|
| `usuario` | `ubicacion.crear`, `ubicacion.eliminar_propia`, `ubicacion.reportar`, `perfil.gestionar_propio`. |
| `moderador` | Los anteriores, además de `ubicacion.eliminar_cualquiera` y `ubicacion.aprobar`. |
| `administrador` | `*` (todos los permisos). |

`SesionAutorizador` devuelve falso sin rol; un valor de rol no reconocido se convierte a `usuario`. `exigirPermiso` devuelve 401 si no hay ID de sesión y 403 si la sesión no tiene permiso ([autorizador](../src/Shared/Infraestructura/Seguridad/SesionAutorizador.php#L21-L48)).

Los casos de uso conectados a las rutas aplican estas reglas:

- Crear: requiere `ubicacion.crear`, por lo tanto los tres roles pueden crear mediante el backend ([crear](../src/UbicacionesFuncionalidad/Aplicacion/CasosDeUso/CrearUbicacionCasoDeUso.php#L24-L27), [permisos](../src/Shared/Dominio/Seguridad/RolUsuario.php#L19-L42)).
- Eliminar: si no puede eliminar cualquiera, debe tener permiso de eliminar propias y ser propietario. Moderador y administrador tienen el permiso global ([eliminar](../src/UbicacionesFuncionalidad/Aplicacion/CasosDeUso/EliminarUbicacionCasoDeUso.php#L22-L39)).
- Listar propias y gestionar perfil requieren `perfil.gestionar_propio` ([listar propias](../src/UbicacionesFuncionalidad/Aplicacion/CasosDeUso/ListarUbicacionesUsuarioCasoDeUso.php#L21-L24), [perfil](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/ActualizarApodoCasoDeUso.php#L23-L26)).

La restricción de que solo el administrador use clic de mapa y otros roles utilicen GPS se ejecuta en [ControladorUbicaciones](../public/js/Presentacion/Ubicaciones/ControladorUbicaciones.js#L39-L53), que captura GPS y comprueba la igualdad de las coordenadas en el submit ([captura y control](../public/js/Presentacion/Ubicaciones/ControladorUbicaciones.js#L55-L89)). **La API no recibe un indicador fiable de “origen GPS”** y `CrearUbicacionCasoDeUso` solo ve valores numéricos; la regla de origen es frontend y puede ser omitida por un cliente que invoque la API directamente. No debe interpretarse como garantía de GPS auténtico.

Las opciones del dashboard son etiquetas/interfaz. No se encontraron rutas ni controladores para reportes, aprobación, auditoría, configuración o administración de usuarios; las rutas existentes están enumeradas arriba ([opciones UI](../public/js/Presentacion/Shared/ConstructorDashboard.js#L8-L49), [rutas de usuario](../src/UsuariosFuncionalidad/UsuariosModulo.php#L48-L60), [rutas de ubicación](../src/UbicacionesFuncionalidad/UbicacionesModulo.php#L24-L32)).

## 3. Reglas de usuarios y autenticación

| Regla | Implementación |
|---|---|
| Al registrar, apodo y correo se recortan; se rechazan duplicados. | [RegistrarUsuarioCasoDeUso](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/RegistrarUsuarioCasoDeUso.php#L82-L98); el DAO consulta solo cuentas no eliminadas ([UsuarioDAO](../src/UsuariosFuncionalidad/Infraestructura/Persistencia/UsuarioDAO.php#L45-L91)). |
| Contraseña de alta, cambio y restablecimiento: mínimo 8 caracteres; se guarda `password_hash(..., PASSWORD_DEFAULT)`. | [registro](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/RegistrarUsuarioCasoDeUso.php#L99-L106), [cambio](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/CambiarContrasenaCasoDeUso.php#L35-L53), [restablecimiento](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/RestablecerContrasenaCasoDeUso.php#L28-L34). |
| Inicio de sesión compara el hash, exige cuenta verificada y usa mensaje genérico para correo inexistente o clave incorrecta. | [LoginCasoDeUso](../src/AuthFuncionalidad/Aplicacion/CasosDeUso/LoginCasoDeUso.php#L29-L61). |
| Login regenera el identificador de sesión. Cookie HttpOnly, SameSite Strict, modo estricto; Secure según HTTPS detectado. | [NativaPhpSesionUsuario](../src/Shared/Infraestructura/Servicios/NativaPhpSesionUsuario.php#L11-L37). |
| Enlace de activación: token aleatorio de 32 bytes, validez 24 horas, un solo uso. | [generación](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/GenerarTokenConfirmacionUsuarioCasoDeUso.php#L25-L53), [consumo](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/VerificacionTokenConfirmacionUsuarioCasoDeUso.php#L27-L57). |
| Recuperación: respuesta no revela si el correo existe; token `RECUPERACION`, expira en una hora, se elimina al consumir. | [solicitud](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/SolicitarRecuperacionContrasenaCasoDeUso.php#L27-L78), [restablecimiento](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/RestablecerContrasenaCasoDeUso.php#L26-L79). |
| Cambiar correo pone la cuenta sin verificar; eliminar cuenta requiere confirmar contraseña y es soft delete. | [ActualizarCorreo](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/ActualizarCorreoCasoDeUso.php#L45-L79), [EliminarCuenta](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/EliminarCuentaUsuarioCasoDeUso.php#L26-L55). |
| Verificación de sesión consulta de nuevo el usuario activo; si fue dado de baja, cierra la sesión. | [VerificarSesionCasoDeUso](../src/AuthFuncionalidad/Aplicacion/CasosDeUso/VerificarSesionCasoDeUso.php#L25-L66). |

La entidad `UsuarioEntidad` valida apodo no vacío, correo válido y hash no vacío ([invariantes](../src/UsuariosFuncionalidad/Dominio/Entidades/UsuarioEntidad.php#L10-L20), [mutaciones](../src/UsuariosFuncionalidad/Dominio/Entidades/UsuarioEntidad.php#L52-L82)). Los tokens aceptan solo `VERIFICACION` o `RECUPERACION` ([TokenConfirmacionUsuarioEntidad](../src/UsuariosFuncionalidad/Dominio/Entidades/TokenConfirmacionUsuarioEntidad.php#L10-L69)).

## 4. Reglas de ubicaciones

1. **Autorización y campos:** controlador exige sesión, título no vacío y latitud/longitud numéricas. El ID de usuario no se toma del cliente: se obtiene de la sesión ([UbicacionControlador](../src/UbicacionesFuncionalidad/Http/UbicacionControlador.php#L87-L130)).
2. **Título:** mínimo cinco caracteres y debe contener al menos tres letras consecutivas de las letras permitidas por la expresión regular; máximo 120 por invariante de entidad. Revisa [caso de uso](../src/UbicacionesFuncionalidad/Aplicacion/CasosDeUso/CrearUbicacionCasoDeUso.php#L28-L43) y [entidad](../src/UbicacionesFuncionalidad/Dominio/Entidades/UbicacionEntidad.php#L59-L79).
3. **Descripción y clasificaciones:** si la descripción llega nula o vacía, el caso de uso guarda `sin descripción`. El título y las seis clasificaciones son obligatorios; los valores se validan contra `OpcionesNuevaUbicacion` antes de construir la entidad.
4. **Rango de coordenadas:** latitud de -90 a 90, longitud de -180 a 180 ([entidad](../src/UbicacionesFuncionalidad/Dominio/Entidades/UbicacionEntidad.php#L71-L79); el front también impone rangos HTML en [modal](../public/componentes/modal-ubicacion.html#L24-L31)).
5. **Geocerca nacional:** si `GEO_MODO=nacional`, la latitud debe estar entre -4.23 y 13.50, y longitud entre -81.80 y -66.85 ([regla](../src/UbicacionesFuncionalidad/Aplicacion/CasosDeUso/CrearUbicacionCasoDeUso.php#L80-L92)).
6. **Geocerca circular:** de otro modo usa `GEO_CENTRO_LAT`, `GEO_CENTRO_LNG`, `GEO_RADIO_KM` (por defecto 4.8133, -75.6961 y 40 km) y Haversine ([cálculo](../src/UbicacionesFuncionalidad/Aplicacion/CasosDeUso/CrearUbicacionCasoDeUso.php#L94-L139)).
7. **No duplicar puntos próximos:** se consultan todas las ubicaciones activas y se rechaza una separación menor de 0.025 km (25 m) ([comparación](../src/UbicacionesFuncionalidad/Aplicacion/CasosDeUso/CrearUbicacionCasoDeUso.php#L112-L125)).
8. **Eliminación lógica:** una ubicación deja de listarse al establecer `fecha_eliminacion`; la consulta comunitaria también excluye usuarios dados de baja ([UbicacionDAO](../src/UbicacionesFuncionalidad/Infraestructura/Persistencia/UbicacionDAO.php#L60-L115)).

La API devuelve configuración de geocerca junto a ubicaciones públicas ([respuesta y variables](../src/UbicacionesFuncionalidad/Http/UbicacionControlador.php#L33-L58)). El archivo de ejemplo configura `GEO_MODO=nacional`; esto no prueba cuál es el valor del `.env` local ([variables de ejemplo](../.env.example#L25-L34)). Los límites visuales del mapa Leaflet son cercanos, pero no idénticos a los límites de validación; es el backend quien decide ([vista nacional](../public/js/Infraestructura/Map/LeafletMapAdapter.js#L54-L71)).

## 5. Rate limiting, errores y transacciones

- `public/index.php` aplica 6 peticiones por 300 segundos a cada ruta crítica (`/login`, registro, recuperación y restablecimiento), y 90 por 60 segundos en el bucket general para otras rutas ([configuración](../public/index.php#L78-L99)).
- `RateLimiter` usa hash SHA-256 de IP como nombre de archivo, bloqueo exclusivo de archivo y ventanas por ruta; tras más del doble del límite puede extender la expiración hasta 900 segundos. La purga ocurre aleatoriamente 1 de cada 15 peticiones y elimina archivos inactivos más de 30 minutos ([RateLimiter](../core/RateLimiter.php#L23-L95), [IP y limpieza](../core/RateLimiter.php#L99-L159)). Si no puede abrir el archivo de contador, el código permite la petición ([fail-open](../core/RateLimiter.php#L36-L41)).
- El manejador global devuelve errores de negocio/validación como JSON 4xx/422; errores técnicos se registran en log y responden 500 genérico ([ManejadorGlobalExcepciones](../src/Shared/Http/ManejadorGlobalExcepciones.php#L23-L75)).
- `PdoGestorTransacciones` abre transacción raíz, hace commit en éxito y rollback ante excepción; si ya hay transacción, no vuelve a abrirla ([transacción](../src/Shared/Infraestructura/Persistencia/PdoGestorTransacciones.php#L20-L49)).
- `ConnexionDB` usa PDO con errores como excepciones, fetch asociativo y prepares no emulados ([conexión](../core/ConnexionDB.php#L19-L69)). El DAO usa consultas preparadas para entradas variables ([UsuarioDAO](../src/UsuariosFuncionalidad/Infraestructura/Persistencia/UsuarioDAO.php#L25-L46), [UbicacionDAO](../src/UbicacionesFuncionalidad/Infraestructura/Persistencia/UbicacionDAO.php#L17-L57)).

## 6. Persistencia observada

No hay migraciones ni DDL SQL en el inventario fuente revisado. Los nombres de tablas/campos aquí descritos son los que emplean las consultas y requieren contrastarse con el esquema MySQL instalado.

| Tabla inferida por los DAOs | Campos y comportamiento visible en consultas |
|---|---|
| `usuarios` | `pk_id_usuario`, `apodo_usuario`, `correo_usuario`, `contrasena_hash_usuario`, `esta_verificado_usuario`, `rol`, `fecha_eliminacion`; la baja escribe fecha y las búsquedas habituales ignoran las filas dadas de baja. | [UsuarioDAO](../src/UsuariosFuncionalidad/Infraestructura/Persistencia/UsuarioDAO.php#L25-L156) |
| `tokens_confirmacion_usuario` | `pk_id_token`, `fk_id_usuario`, `token_verificacion`, `tipo_token`, `fecha_expiracion`; los tokens se buscan por token y se borran al usarse. | [TokenConfirmacionUsuarioDAO](../src/UsuariosFuncionalidad/Infraestructura/Persistencia/TokenConfirmacionUsuarioDAO.php#L10-L83) |
| `ubicaciones` | `pk_id_ubicacion`, `fk_id_usuario`, `titulo`, `descripcion`, `latitud`, `longitud`, `tipo_infraestructura`, `estado_infraestructura`, `riesgos_directos`, `riesgos_indirectos`, `actividades_delictivas`, `condiciones_sociales`, `fecha_creacion`, `fecha_eliminacion`; listas filtran soft delete y ordenan por fecha descendente. La descripción usa `sin descripción` cuando está vacía. | [UbicacionDAO](../src/UbicacionesFuncionalidad/Infraestructura/Persistencia/UbicacionDAO.php), [migración](../migrations/20261126_agregar_clasificaciones_ubicaciones.sql) |

La conexión se obtiene de `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`; `EnvLoader` lee `.env` y ofrece defaults solo donde se indican ([variables usadas](../core/ConnexionDB.php#L24-L46), [cargador](../core/EnvLoader.php#L10-L63)). La documentación no incluye valores secretos. El módulo de usuarios selecciona `LogLocalEnviadorCorreo` cuando `APP_ENV=local`; en otro caso enlaza SMTP ([selección](../src/UsuariosFuncionalidad/UsuariosModulo.php#L20-L45)). Los enlaces de cuenta dependen de `APP_URL` ([registro](../src/UsuariosFuncionalidad/Aplicacion/CasosDeUso/RegistrarUsuarioCasoDeUso.php#L60-L69)).
