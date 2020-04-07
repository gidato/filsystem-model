<?php

chdir(dirname(__DIR__));
include('vendor/autoload.php');

$dir = dirname(__DIR__) . '/src/Models/';

$output = file_get_contents(__DIR__ . '/prefix.md');

foreach (glob("$dir*") as $file)
{
    $name = substr(basename($file), 0, -4);
    $class = 'Gidato\\Filesystem\\Models\\' . $name;
    $output .= getClassStructureOuput($class, $name);
}

file_put_contents(dirname(__DIR__) . '/README.md', $output);


/* -------------------------------------------------------------------------- */


function getClassStructureOuput($class, $name)
{
    $reflected = new \ReflectionClass($class);

    $type = $reflected->isInterface()
        ? 'Interface'
        : ( $reflected->isAbstract() ? 'Abstract Class' : 'Class');

    $extends = $reflected->getParentClass();
    $extends = (empty($extends)) ? '' : ' extends '.stripNamespace($extends->name);

    $interfaces = array_map(function ($name) { return stripNamespace($name); }, $reflected->getInterfaceNames());
    $interfaces = empty($interfaces) ? '' : ' implements ' . implode(', ', $interfaces);

    $output = "\n\n```php\n";
    $output .= "<?php\n";
    $output .= "namespace Gidato\\Filesystem\\Models;\n\n";
    $output .= "{$type} {$name}{$extends}{$interfaces} {\n";

    $methods = getClassMethods($reflected);

    if (count($methods['self'])) {
        $output .= "\n  /* Methods */\n";
        foreach ($methods['self'] as $method) {
            $output .= getMethodDefinition($method);
        }
    }

    if (count($methods['inherited'])) {
        $output .=  "\n  /* Inherited Methods */\n";
        foreach ($methods['inherited'] as $from => $inheritedMethods) {
            foreach ($inheritedMethods as $method) {
                $output .= getMethodDefinition($method, $from);
            }
        }
    }

    $output .= "}\n```";
    $output .= "\n\n";
    return $output;
}

function getMethodDefinition($method, $className = null)
{
    if ('__get' == $method->name) {
        return '';
    }

    $static = $method->isStatic() ? 'static ' : '';
    $parameters = getMethodParameters($method);

    $className = empty($className) ? '' : $className.'::';

    return "  public {$static} {$className}{$method->name}($parameters);\n";
}

function getClassMethods($reflected)
{
    $methods = [
        'self' => [],
        'inherited' => []
    ];
    foreach ($reflected->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
    {
        if ($reflected == $method->getDeclaringClass()) {
            $methods['self'][] = $method;
        } else {
            $declaredIn = $method->getDeclaringClass()->name;
            $declaredInName = current(array_reverse(explode('\\', $declaredIn)));
            $methods['inherited'][$declaredInName][] = $method;
        }
    }
    return $methods;
}

function getMethodParameters($method)
{
    $parameters = [];
    foreach ($method->getParameters() as $reflectionParameter) {
        $parameter = ($reflectionParameter->hasType() && $reflectionParameter->allowsNull()) ? '?' : '';
        $parameter .= stripNamespace($reflectionParameter->hasType() ? $reflectionParameter->getType() . ' ' : '');
        $parameter .= '$' . $reflectionParameter->getName();
        if ($reflectionParameter->isOptional()) {
            $parameter .= ' = ' . $reflectionParameter->getDefaultValue();
        }
        $parameters[] = $parameter;
    }
    return implode(', ', $parameters);
}

function stripNamespace($name)
{
    if (preg_match('/^Gidato\\\\Filesystem\\\\Models\\\\(.*)$/', $name, $matches)) {
        return $matches[1];
    }

    return $name;
}
