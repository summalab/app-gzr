<?php

declare(strict_types=1);

namespace Core;

use ReflectionClass;
use Exception;
use Closure;

class Contenedor
{
    /**
     * Almacena las definiciones personalizadas o mapeos de interfaces a clases.
     * @var array<string, mixed>
     */
    private array $bindings = [];

    /**
     * Caché de instancias únicas en memoria (Patrón Singleton por petición).
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Registra de forma manual una interfaz o una configuración específica (ej. la conexión PDO).
     * 
     * @param string $abstract El nombre de la interfaz o clase abstracta.
     * @param Closure|string $concrete El closure de fabricación o el nombre de la clase real.
     * @return void
     */
    public function bind(string $abstract, Closure|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Obtiene una instancia completamente resuelta con todas sus dependencias inyectadas.
     * 
     * @param string $id El nombre calificado de la clase o interfaz a resolver.
     * @return mixed
     * @throws Exception Si la clase no se puede resolver o carece de tipos en el constructor.
     */
    public function get(string $id): mixed
    {
        // 1. Si la instancia ya fue creada durante esta petición HTTP, la devolvemos (Singleton)
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. Revisamos si existe un mapeo explícito (como una interfaz enlazada a su implementación)
        $concrete = $this->bindings[$id] ?? $id;

        // 3. Si el mapeo es una función anónima (Closure), la ejecutamos pasándole el contenedor
        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } else {
            // 4. Si es una cadena de texto con el nombre de la clase, usamos Autowiring automático
            $object = $this->resolve($concrete);
        }

        // 5. Guardamos en caché de memoria interna de la petición para reutilizarla
        $this->instances[$id] = $object;

        return $object;
    }

    /**
     * Motor automático de resolución mediante la API de Reflexión de PHP.
     * Analiza recursivamente los constructores para instanciar el árbol de objetos.
     * 
     * @param string $class Nombre completo de la clase.
     * @return object
     * @throws Exception
     */
    private function resolve(string $class): object
    {
        // Creamos el espejo óptico de la clase usando reflexión
        $reflector = new ReflectionClass($class);

        // Validación defensiva básica: verificar si la clase se puede instanciar (no es abstracta ni interfaz)
        if (!$reflector->isInstantiable()) {
            throw new Exception("Error en Autowiring: La clase [{$class}] no se puede instanciar.");
        }

        // Extraemos el constructor de la clase
        $constructor = $reflector->getConstructor();

        // Si la clase no tiene constructor, se instancia directamente de forma limpia
        if ($constructor === null) {
            return new $class();
        }

        // Analizamos los parámetros requeridos por el constructor
        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            // Obtenemos el tipo nativo declarado en el argumento (ej. PDO, UserDAO)
            $type = $parameter->getType();

            if ($type === null) {
                throw new Exception(
                    "Error de Diseño: El parámetro [\${$parameter->getName()}] en la clase [{$class}] carece de un tipo explícito definido."
                );
            }

            // Extraemos el nombre textual del tipo de la clase requerida
            $typeName = $type->getName();

            // RECURSIVIDAD: Le pedimos al contenedor que resuelva la dependencia del argumento.
            // Si UserDAO requiere un PDO, el contenedor saltará a resolver el PDO primero.
            $dependencies[] = $this->get($typeName);
        }

        // Instanciamos el objeto pasando el arreglo de dependencias resueltas en el orden correcto
        return $reflector->newInstanceArgs($dependencies);
    }
}
