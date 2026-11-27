# Documentación técnica de app-gzr

## Alcance

Esta documentación recorre la aplicación desde el servidor web y la carga de la landing page hasta el navegador, los endpoints PHP, los casos de uso, las reglas de dominio y las consultas de persistencia. Los enlaces a código apuntan a los archivos actuales del repositorio y, cuando es útil, a una línea concreta (`#Lnn`).

La revisión se hizo sobre el código fuente de `public/`, `core/` y `src/`, además de la configuración de raíz y `.env.example`. No se inspeccionaron los valores del archivo `.env`, los archivos de `storage/` ni el contenido de `.git`; esos elementos pueden contener configuración local, datos o registros y no son parte de la documentación funcional. No hay un esquema SQL completo versionado; las tablas y columnas se deducen principalmente de los DAOs. La migración disponible amplía a `VARCHAR(128)` las columnas de clasificación de ubicaciones para alojar todas las opciones del dominio.

## Documentos

1. [01 - Recorrido y arquitectura](./01-recorrido-arquitectura.md): punto de entrada, capas, carga de la interfaz, servicios y diagrama de clases.
2. [02 - Acciones y flujos](./02-acciones-y-flujos.md): acciones de visitante y usuario, autenticación, cuenta, ubicaciones y diagramas de flujo Mermaid.
3. [03 - API, reglas y persistencia](./03-api-reglas-persistencia.md): rutas HTTP, roles, reglas exactas, validaciones, seguridad, configuración e interacción con MySQL.

## Lectura recomendada

Comienza por el documento 01 para entender el arranque y las dependencias; continúa con el 02 para seguir las interacciones del usuario, y usa el 03 como referencia de endpoints y reglas del backend.

## Convenciones y límites

- Los requisitos HTML y validaciones JavaScript mejoran la experiencia, pero el backend vuelve a validar los datos que protege.
- Los permisos que realmente protegen operaciones se comprueban en el backend. La visibilidad de botones o el comportamiento del mapa en el navegador no sustituyen esa autorización.
- El código presenta tres roles: `usuario`, `moderador` y `administrador`. Los permisos declarados, los casos de uso conectados a rutas y las acciones visuales son cosas distintas; se documentan por separado.
- Los diagramas representan los flujos que están implementados. Una acción visual que no tenga ruta o manejador funcional no se interpreta como una capacidad disponible en el backend.
