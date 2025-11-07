<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIGG Contadores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <Script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></Script>


    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance

</head>

<body class="min-h-screen bg-gray-50">


    <nav class="bg-white dark:bg-gray-900 fixed w-full z-20 top-0 start-0 border-b border-gray-200 dark:border-gray-600">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto">
            <a href="{{ url('/') }}" class="flex items-center space-x-3 rtl:space-x-reverse">
                <img src="/img/logo.png" class="h-20" alt="Flowbite Logo">
            </a>
            <div class="flex md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                <a  href="{{route('Clientes.portal')}}"
                    class="text-white bg-amber-900 hover:bg-amber-800 focus:ring-4 focus:outline-none 
        focus:ring-amber-300 font-medium rounded-lg text-sm px-4 py-2 text-center dark:bg-zinc-500 
        dark:hover:bg-amber-700 dark:focus:ring-amber-800">Clientes</a>
                <button data-collapse-toggle="navbar-sticky" type="button"
                    class="inline-flex items-center p-2 w-10 h-10 
        justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 
        focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                    aria-controls="navbar-sticky" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M1 1h15M1 7h15M1 13h15" />
                    </svg>
                </button>
            </div>
            <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-sticky">
                <ul
                    class="flex flex-col p-4 md:p-0 mt-4 font-medium border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                    <li>
                        <a href="#"
                            class="block py-2 px-3 text-white bg-amber-700 rounded-sm md:bg-transparent md:text-amber-700
           md:p-0 md:dark:text-zinc-500"
                            aria-current="page">Inicio</a>
                    </li>
                    <li>
                        <a href="#conocenos"
                            class="block py-2 px-3 text-gray-900 rounded-sm hover:bg-gray-100 
          md:hover:bg-transparent md:hover:text-amber-700 md:p-0 md:dark:hover:text-zinc-500 
          dark:text-white dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent
           dark:border-gray-700">Nosotros</a>
                    </li>
                    <li>
                        <a href="#servicios"
                            class="block py-2 px-3 text-gray-900 rounded-sm hover:bg-gray-100 
          md:hover:bg-transparent md:hover:text-amber-700 md:p-0 md:dark:hover:text-zinc-500 
          dark:text-white dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent 
          dark:border-gray-700">Servicios</a>
                    </li>
                    <li>
                        <a href="#contacto"
                            class="block py-2 px-3 text-gray-900 rounded-sm hover:bg-gray-100 
          md:hover:bg-transparent md:hover:text-amber-700 md:p-0 md:dark:hover:text-zinc-500 
          dark:text-white dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent 
          dark:border-gray-700">Contacto</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Slider -->
    <section class="bg-white dark:bg-gray-900" id="slider">
        <div class="relative h-screen overflow-hidden">
            <div class="absolute inset-0">
                <img id="slide-image" class="w-full h-full object-cover" alt="Slide">
                <div class="absolute inset-0 bg-black bg-opacity-50"></div>
            </div>

            <div class="absolute inset-0 flex items-center justify-center text-white">
                <div class="text-center px-4">
                    <h1 id="slide-title" class="text-5xl font-bold mb-4"></h1>
                    <p id="slide-description" class="text-xl mb-8"></p>
                    <a href="#contacto"
                        class="w-fit bg-zinc-500 hover:bg-amber-700 px-8 py-3 rounded-full font-semibold flex items-center mx-auto btn-primary ">
                        Contactanos
                        <i data-lucide="arrow-right" class="ml-2"></i>
                </a>
                </div>
            </div>
        </div>
        <button id="prev-slide"
            class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-30 p-2 rounded-full hover:bg-opacity-50">
            <i data-lucide="chevron-left" class="text-white"></i>
        </button>
        <button id="next-slide"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-30 p-2 rounded-full hover:bg-opacity-50">
            <i data-lucide="chevron-right" class="text-white"></i>
        </button>
        </div>
    </section>
    <!----nosotros ----->


    <section class="text-gray-700 body-font border-t border-gray-200" id="conocenos">
        <div class="container px-5 py-24 mx-auto ">
            <div class="flex flex-col text-center w-full mb-20">
                <h2 class="text-xs text-amber-600 tracking-widest font-medium title-font mb-1">BIENVENIDOS A RIGG
                    CONTADORES</h2>
                <h1 class="text-4xl font-bold text-center mb-16">Tus Aliados en Finanzas y Contabilidad</h1>
            </div>

            <div class="gap-16 items-center   mx-auto max-w-screen-xl lg:grid lg:grid-cols-2 lg:py-16 lg:px-6">
                <div class="font-light text-gray-500 sm:text-lg dark:text-gray-400 text-justify">
                    <p class="mb-4">En nuestro despacho contable nos especializamos en servicios de contabilidad,
                        auditoría y asesoría fiscal para empresas y particulares. Gestionamos la contabilidad de
                        nuestros clientes, asegurando el cumplimiento oportuno y conforme a la ley de sus obligaciones
                        fiscales y financieras.</p>
                    <p>Además, ofrecemos consultoría estratégica para optimizar su gestión financiera y apoyar
                        decisiones informadas que impulsen el crecimiento y la estabilidad de sus negocios.</p>
                </div>
                <div class="grid grid-cols-1 gap-4 mt-8">
                    <img class="w-full rounded-lg"
                        src="/img/5.jpg"
                        alt="office content 1">
                  
                </div>
            </div>
        </div>
    </section>


    <!-- Services Section -->

    <section class="text-gray-700 body-font border-t border-gray-200" id="servicios">
        <div class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4">
                <h1 class="text-4xl font-bold text-center mb-16">Nuestros servicios</h1>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white p-8 rounded-xl shadow-lg self-start">
                        <i data-lucide="building-2" class="text-amber-600 mb-4 w-8 h-8"></i>
                        <h3 class="text-xl font-semibold mb-4">Contabilidad Financiera</h3>
                        <p class="text-gray-600 mb-4">Gestión integral de tu contabilidad.</p>
                        <p class="text-gray-600 mb-4 oculto hidden  transition-all duration-300 ease-in-out">En RIGG
                            Contadores, ofrecemos un servicio completo de contabilidad financiera que se adapta a las
                            necesidades de tu negocio. Nuestro equipo de contadores profesionales se encarga de
                            registrar, clasificar y analizar todas las transacciones financieras para que tengas una
                            visión clara de la salud económica de tu empresa. Con este servicio, recibirás informes
                            periódicos que te ayudarán a tomar decisiones informadas y estratégicas. Nos aseguramos de
                            cumplir con todas las regulaciones fiscales y normativas contables, brindándote tranquilidad
                            y confianza en la gestión de tus finanzas.</p>
                        <button class="text-zinc-500 font-semibold toggle-paragraph">Mostrar Mas →</button>
                    </div>
                    <div class="bg-white p-8 rounded-xl shadow-lg self-start">
                        <i data-lucide="target" class="text-amber-600 mb-4 w-8 h-8"></i>
                        <h3 class="text-xl font-semibold mb-4">Asesoría fiscal</h3>
                        <p class="text-gray-600 mb-4">Optimiza tus obligaciones fiscales.</p>

                        <p class="text-gray-600 mb-4 oculto hidden  transition-all duration-300 ease-in-out">Nuestra
                            asesoría fiscal se centra en ofrecerte estrategias personalizadas para optimizar tus
                            obligaciones tributarias. En RIGG Contadores, entendemos que cada empresa es única y por
                            eso, proporcionamos un análisis exhaustivo de tu situación fiscal. Te ayudamos a identificar
                            deducciones y beneficios fiscales que pueden reducir tu carga impositiva. Además, te
                            mantenemos informado sobre cambios en las leyes fiscales que puedan afectar a tu negocio.
                            Con nuestro apoyo, podrás cumplir con tus obligaciones fiscales de manera eficiente y sin
                            sorpresas desagradables.</p>
                        <button class="text-zinc-500 font-semibold toggle-paragraph">Mostrar Mas →</button>
                    </div>
                    <div class="bg-white p-8 rounded-xl shadow-lg self-start">
                        <i data-lucide="briefcase" class="text-amber-600 mb-4 w-8 h-8"></i>
                        <h3 class="text-xl font-semibold mb-4">Contabilidad de nómina</h3>
                        <p class="text-gray-600 mb-4">Gestión eficiente de tu nómina.</p>

                        <p class="text-gray-600 mb-4 oculto hidden  transition-all duration-300 ease-in-out">La
                            contabilidad de nómina es esencial para el buen funcionamiento de tu empresa y en RIGG
                            Contadores te ofrecemos un servicio integral en este aspecto. Nos encargamos de calcular
                            correctamente los salarios, retenciones y aportaciones de seguridad social, asegurando el
                            cumplimiento de la legislación laboral vigente. Nuestro objetivo es que tu equipo reciba sus
                            pagos puntualmente y con precisión. Además, te proporcionamos informes detallados sobre la
                            nómina, para que tengas un control total de los gastos relacionados con tu personal.</p>
                        <button class="text-zinc-500 font-semibold toggle-paragraph">Mostrar Mas →</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="bg-zinc-500 py-16 my-20">
            <div class="max-w-7xl mx-auto px-4">
                <div id="stats-grid" class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <!-- Stats will be inserted here by JavaScript -->
                </div>
            </div>
        </div>
    </section>


    <section class="text-gray-700 body-font border-t border-gray-200" id="partners">
        <div class="container px-5 py-24 mx-auto">
            <div class="flex flex-col text-center w-full mb-20">
                <h2 class="text-xs text-amber-600 tracking-widest font-medium title-font mb-1">PORQUE NOS GUSTA HACER EQUIPO</h2>
                <h1 class="text-4xl font-bold text-center mb-16">ClickBalance Partners</h1>
            </div>

            <div class="gap-16 items-center   mx-auto max-w-screen-xl lg:grid lg:grid-cols-2 lg:py-16 lg:px-6">
                <div class="font-light text-gray-500 sm:text-lg dark:text-gray-400 text-justify">
                    <p class="mb-4">Aliados con ClickBalance para llevar tu negocio al siguiente nivel.
                        Como partners de ClickBalance, te ofrecemos soluciones inteligentes que integran tecnología y 
                        contabilidad para simplificar tu administración, optimizar procesos y ayudarte a tomar decisiones
                        más rentables. ¡Haz crecer tu empresa con herramientas de última generación y asesoría experta! .</p>
                    
                </div>
                <div class="grid grid-cols-1 gap-4 mt-8">
                    <img class="w-100 rounded-lg"
                    src="/img/cb.png"

                        src="/img/8.jpg"
                        alt="office content 1">
                        <img class="w-100 rounded-lg"
                        src="/img/7.jpg"

                        alt="office content 1">
                </div>
            </div>
        </div>
    </section>



    <section  class="py-16 flex items-center justify-center min-h-screen text-gray-700 body-font border-t border-gray-200" id="contacto">
        <div class="container px-5 py-24 mx-auto flex sm:flex-nowrap flex-wrap">
            <div
                class="lg:w-2/3 md:w-1/2 bg-blue-300 rounded-lg overflow-hidden sm:mr-10 p-10 flex items-end justify-start relative">
                <iframe width="100%" height="100%" class="absolute inset-0" frameborder="0" title="map"
                    marginheight="0" marginwidth="0" scrolling="no"
                    src="https://www.google.com/maps/embed?pb=!1m10!1m8!1m3!1d474.3313425769061!2d-92.93715841272969!3d17.99497908424674!3m2!1i1024!2i768!4f13.1!5e0!3m2!1ses!2smx!4v1749091745284!5m2!1ses!2smx"></iframe>
                <div class="bg-white relative flex flex-wrap py-6 rounded shadow-md">
                    <div class="lg:w-1/2 px-6">
                        <h2 class="font-bold text-gray-dark">ADDRESS</h2>
                        <p class="mt-1">Av Paseo Tabasco 1120, Jesus Garcia, 86050 Villahermosa, Tab.</p>
                    </div>
                    <div class="lg:w-1/2 px-6 mt-4 lg:mt-0">
                        <h2 class="font-bold text-gray-dark">EMAIL</h2>
                        <a href="mailto:your@email.com" class="text-gray-dark leading-relaxed">soporte@riggcontadores.com</a>
                        <h2 class="font-bold text-gray-dark mt-4">TELEFONO</h2>
                        <a href="tel:123-456-7890" class="leading-relaxed"> +52 9931301111</a>
                    </div>
                </div>
            </div>

            <div class="lg:w-1/3 md:w-1/2 bg-white flex flex-col md:ml-auto w-full md:py-8 mt-8 md:mt-0">
                <h2 class="text-zinc-500 text-lg mb-2 font-medium">¡Ponte en contacto!</h2>
                <p class="mb-5 text-gray-txt">¿Tienes preguntas, sugerencias o simplemente quieres saludarnos? ¡Nos
                    encantaría saber de ti! Escríbenos y te responderemos lo antes posible.</p>
                <p></p>
                <div class="relative mb-4">
                    <label for="name" class="leading-7 text-sm text-gray-dark">Nombre</label>
                    <input type="text" id="name" name="name"
                        class="w-full bg-white rounded border border-gray-txt  text-base outline-none text-gray-txt py-1 px-3 leading-8">
                </div>
                <div class="relative mb-4">
                    <label for="email" class="leading-7 text-sm text-gray-dark">Email</label>
                    <input type="email" id="email" name="email"
                        class="w-full bg-white rounded border border-gray-txt text-base outline-none text-gray-txt py-1 px-3 leading-8">
                </div>
                <div class="relative mb-4">
                    <label for="message" class="leading-7 text-sm text-gray-dark">Mensaje</label>
                    <textarea id="message" name="message"
                        class="w-full bg-white rounded border border-gray-txt  h-32 text-base outline-none text-gray-txt py-1 px-3 resize-none"></textarea>
                </div>
                <button
                    class="text-white bg-amber-900  border-0 py-3 px-6 focus:outline-none hover:bg-zinc-500 rounded text-lg">Enviar</button>
            </div>
        </div>
    </section>
    <!-- Footer -->


    <footer class="bg-stone-700 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">RIGG Contadores</h3>
                    <p class="text-gray-400"> "Transparencia y control para tu éxito financiero.”</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Inicio</a></li>
                        <li><a href="#servicios" class="hover:text-white">Servicios</a></li>
                        <li><a href="#conocenos" class="hover:text-white">Conocenos</a></li>
                        <li><a href="#contacto" class="hover:text-white">Contacto</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Contact</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center">
                            <i data-lucide="phone" class="w-4 h-4 mr-2"></i>
                            + 52 9931301111
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="mail" class="w-4 h-4 mr-2"></i>
                            contacto@rigg.com                        </li>
                        {{-- <li class="flex items-center">
                            <i data-lucide="message-square" class="w-4 h-4 mr-2"></i>
                            Live Chat
                        </li> --}}
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Siguenos</h3>
                    <div class="flex space-x-4">
                        {{-- <a href="#" class="hover:text-amber-400">
                            <i data-lucide="globe" class="w-6 h-6"></i>
                        </a> --}}
                        <a href="#" class="hover:text-amber-400">
                            <i data-lucide="instagram" class="w-6 h-6"></i>
                        </a>
                        <a href="#" class="hover:text-amber-400">
                            <i data-lucide="facebook" class="w-6 h-6"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>Copyright © 2024 RIGG CONTADORES - Todos los derechos reservados.

                </p>
            </div>
        </div>
    </footer>
    @fluxScripts

</body>

</html>
