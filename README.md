# NOTES BY ALVARO

# Creation step by step

## Boilerplate

1. Install using Laravel Installer. It helps you to select the setup of the framework. `laravel lar25-binance`
   It can be made manually (creatin a blank new project with `composer create-project laravel/laravel {proyecto} --prefer-dist`), but this helps
2. Create the DB, in this case in MySQL, and set it up the connection credentials in `.env`
3. Build the frontend with `npm install`, `npm run build`, and then for developmet `npm run dev`
4. Run the backend server with `art serve` (I created a shortcut for `php artesan` into `art`)
5. Setup Tailwind params in `/tailwind.config.js`

## Create a new route and page /currencies

-   web.php
-   Currencies.vue
-   Add the link in <NavLink> in the Layout, in this case for login users only, in `Layouts/AuthenticatedLayout.vue`

## New controller for everything related to Binance requests:

-   ~~Using Guzzle `composer require guzzlehttp/guzzle`~~ . It was a mistake. It's enough with Http class.
-   Creating controller: `php artisan make:controller BinanceController`
-   For the internal endpoints, I created `binance-routes.php`. Similar to web.php, to be used in Vue, with:
    -   internal endpoints, for example, to update the user's favourite tickers dynamically from Vue.
    -   external endpoints to binance, to retrieve the price of a ticker, or the balance of the user in Binance

## New field in Profile page for binance_token.

-   Creating a migration for the new fields `binance_token` and `fav_tickers`. `binance_token` is edited from the Profile page. Initially I created a new migration for each new field, then I decided to refactor it into one single migration adding both fields.

## Creation of Currencies.vue

-   showing info via API with `binanceApi.ts` in frontend, connecting to routes in `binance-routes.php` (similar to web.php) which call the logic in the controller `BinanceController`.
-   Like this we avoid the CORS problem by calling directly from the frontend.

### Section to add favourite ticker

-   Creation of custom component `InputLookup.vue` for the add to favourites.
-   I created a state to hold the favourite tickers in the page, so we can update it with a js call.and the information in the page updates synamically (we don't need to reload the page)

### Tabs to show 1) list of favourite tickers and 2) user's currency and balances in Binance

-   Adding the state var selectedTab, and created a micro CRUD library to save options in localStorage.

### Add drag and drop to the favourite

-   Installing npm install **vuedraggable**.

### Panel showing the selected ticker, the one we will trade with

-   Added some other UI and UX improvements. When selecting a current ticker, it's saved in localStorage. Reloading the page initializes to that ticker. Deleting a fav ticker adds a prop `isDeleting` to that ticker, showing it semitransparent, for better look before it dissappears.

## Adding websockets to show always current price of selectedTicker.

-   Dismissed. It's too much for this project using a third party helper like pusher or creating our own websockets server. We simply use a setInterval to reload the price every 5 secs.

# Explanation of the project

## Objectivo

Crear un proyecto en Laravel que me permita loguearme y gestionar mi perfil de usuario. Para ello, usaremos Laravel 11, usando el installer de Laravel, que ayuda a configurar un Boilerplate llamado `Breeze`, ya con autentificación, con VueJS e Inertia, que ayuda a comunicar Backend data y Frontend sin necesidad de hacer llamadas a la API.  
Queremos que el usuario pueda introducir en su página de perfil el token de autentificación de su cuenta de Binance, para poder acceder a tu cartera y realizar operaciones en su nombre desde nuestra app.
Queremos crear la página privada /currencies, que muestran la lista del protafolio de usuario, muestra también una lista de los tickers favoritos del usuario, con un input lookup que permite buscar los tickers y añadirlo a la lista de favoritos.
En esa página, Hay una sección llamada Trade que muestra el precio en tiempo real del ticker que has seleccionado (`selectedTicker`).

## Backend:

-   API Service: Crear un servicio dedicado para manejar las peticiones hacia la API de Binance. Este servicio se encargará de las credenciales y la autenticación (realmente no cree un servicio, sencillamente un controlador `BinanceController.php` donde meti toda la logica de backend relacionada con Binance.):
    ``
-   Rutas: Definir rutas que se conecten con el frontend para obtener y mostrar los precios de las monedas, así como permitir futuras operaciones (compra/venta): `/currencies` in `routes/web.php`
-   Controladores: Controladores para interactuar con el servicio de la API y devolver los datos procesados al frontend. `php artisan make:controller BinanceController >> BinanceController.php`, routes in `routes/binance-routes.php`
-   WebSockets (opcional): Para las actualizaciones en tiempo real, puedes implementar WebSockets (por ejemplo, usando Laravel Echo) que permitan recibir cambios de precios en tiempo real.

## Frontend - Vue.js

**Propósito**: Mostrar la información en tiempo real de las monedas y permitir la interacción del usuario.
**Estructura**:

-   Componente de Selección de Moneda: Un dropdown donde el usuario pueda seleccionar la moneda de interés.
-   Componente de Precio en Tiempo Real: Una sección que muestre el precio de la moneda seleccionada. Se actualizaría en tiempo real usando sockets o una llamada periódica a la API.
-   Componente de Operaciones (Futuro): Una interfaz para realizar operaciones de compra y venta.

## Capa de Integración con Binance

Autenticación: Usar las credenciales de tu cuenta para autenticar la conexión con la API de Binance. Esto se gestionará a través de un archivo de configuración o un servicio dedicado en Laravel.
API de Binance:
Utiliza los endpoints para obtener la lista de monedas disponibles (/api/v3/ticker/price).
Obtener el precio de una moneda específica seleccionada por el usuario.
Gestionar las claves de API y los tokens de acceso de forma segura.

## Base de Datos - MySQL

Tablas:
Users: Para almacenar los usuarios del sistema (si es multiusuario o si requieres sesiones de usuario).
Currencies (opcional): Para almacenar información de monedas si deseas hacer caching de los datos.
Operations (futuro): Para almacenar las operaciones realizadas por los usuarios (compra/venta).

## Real-Time Actualización de Precios

Opciones:
WebSockets: Integrar Laravel Echo o Pusher para gestionar actualizaciones en tiempo real.
Polling: Realizar peticiones periódicas a la API para actualizar los precios cada pocos segundos (alternativa menos eficiente pero más sencilla).

## UI con Tailwind CSS

Propósito: Acelerar la creación de la interfaz y hacerla responsive sin necesidad de escribir mucho CSS personalizado.
Beneficios:
Rápido prototipado: Te permite desarrollar rápidamente una interfaz limpia y funcional.
Personalización fácil: Puedes personalizar estilos sin escribir tanto código CSS desde cero.
Alternativas: Si prefieres un framework más completo de UI, podrías considerar Vuetify o BootstrapVue si la velocidad no es la única prioridad.

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-   [Simple, fast routing engine](https://laravel.com/docs/routing).
-   [Powerful dependency injection container](https://laravel.com/docs/container).
-   Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
-   Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
-   Database agnostic [schema migrations](https://laravel.com/docs/migrations).
-   [Robust background job processing](https://laravel.com/docs/queues).
-   [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

-   **[Vehikl](https://vehikl.com/)**
-   **[Tighten Co.](https://tighten.co)**
-   **[WebReinvent](https://webreinvent.com/)**
-   **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
-   **[64 Robots](https://64robots.com)**
-   **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
-   **[Cyber-Duck](https://cyber-duck.co.uk)**
-   **[DevSquad](https://devsquad.com/hire-laravel-developers)**
-   **[Jump24](https://jump24.co.uk)**
-   **[Redberry](https://redberry.international/laravel/)**
-   **[Active Logic](https://activelogic.com)**
-   **[byte5](https://byte5.de)**
-   **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
