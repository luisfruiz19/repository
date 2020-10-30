# Patron Repository

Es una libreria de Laravel que proporciona comandos para generar ModeloRepository y Controlador API.

## Instalacion

Ejecute en el terminal el siguiente comando.

```bash
composer require patronrepository/repository
```

## Uso del Paquete

Una vez instalado el paquete en Laravel, es recomendable utilizar el siguiente comando para exportar generador de comando, las respuesta , reemplazador. Asi mismo el archivo BaseAPIRespository,BaseRepository.

Generando comandos.

Genera el repositorio del modelo.

```bash
php artisan generator repository User
```

Genera el repositorio del Controller API.

```bash
php artisan generator controller User
```

## Contribuciones

Gracias.

## License

[MIT](./LICENSE.md)
