import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Inicializa los íconos Lucide (debes asegurarte de que la librería Lucide ya esté cargada)
lucide.createIcons();

// Slides del Hero
const slides = [
  {
    title: "Contadores en México",
    description: "Soluciones integrales y experiencia comprobada, pensadas para tu empresa",
    image: "/img/1.jpg"
  },
  {
    title: "Despacho Contable",
    description: "Soluciones de negocio para tu empresa",
    image: "/img/2.jpg"
  },
  {
    title: "Equipo de expertos",
    description: "Tú sólo te ocuparás de lo más importante… tu negocio.",
    image: "/img/3.jpg"
  }
];

let currentSlide = 0;

function updateSlide() {
  const slideImage = document.getElementById('slide-image');
  const slideTitle = document.getElementById('slide-title');
  const slideDescription = document.getElementById('slide-description');

  // Transición de salida
  slideImage.classList.remove('visible');
  slideTitle.classList.remove('visible');
  slideDescription.classList.remove('visible');

  setTimeout(() => {
    // Cambiar contenido
    slideImage.src = slides[currentSlide].image;
    slideTitle.textContent = slides[currentSlide].title;
    slideDescription.textContent = slides[currentSlide].description;

    // Transición de entrada
    slideImage.classList.add('visible');
    slideTitle.classList.add('visible');
    slideDescription.classList.add('visible');
  }, 300);
}

document.addEventListener('DOMContentLoaded', () => {
  // 1. Mostrar la primera diapositiva y comenzar el ciclo automático
  updateSlide();
  setInterval(() => {
    currentSlide = (currentSlide + 1) % slides.length;
    updateSlide();
  }, 7000);

  // 2. Seleccionar los botones y agregarles eventos
  const prevBtn = document.getElementById('prev-slide');
  const nextBtn = document.getElementById('next-slide');

  // Asegúrate de que ambos botones existan en el DOM
  if (prevBtn && nextBtn) {
    prevBtn.addEventListener('click', () => {
      currentSlide = (currentSlide - 1 + slides.length) % slides.length;
      updateSlide();
    });

    nextBtn.addEventListener('click', () => {
      currentSlide = (currentSlide + 1) % slides.length;
      updateSlide();
    });
  }

  // 3. Inicializar estadísticas (ya que lo tenías en otro DOMContentLoaded)
  initializeStats();
});

// Función para estadísticas (sin cambios)
const stats = [
  { number: "50+", label: "Clientes" },
  { number: "80+", label: "Proyectos completados" },
  { number: "10+", label: "Miembros del equipo" },
  { number: "99%", label: "Satisfacción del cliente" }
];

function initializeStats() {
  const statsGrid = document.getElementById('stats-grid');
  stats.forEach(stat => {
    const statCard = document.createElement('div');
    statCard.className = 'text-center text-white';
    statCard.innerHTML = `
      <div class="text-4xl font-bold mb-2 stat-number">${stat.number}</div>
      <div class="text-blue-100">${stat.label}</div>
    `;
    statsGrid.appendChild(statCard);
  });
}

// Toggle de párrafos “Mostrar Más / Mostrar Menos”
document.addEventListener('DOMContentLoaded', () => {
  const buttons = document.querySelectorAll('button.toggle-paragraph');

  buttons.forEach(button => {
    button.addEventListener('click', function () {
      const parent = this.closest('div');
      const paragraph = parent.querySelector('.oculto');
      const isVisible = !paragraph.classList.contains('hidden');

      // Ocultar todos los párrafos y resetear botones
      document.querySelectorAll('.oculto').forEach(p => p.classList.add('hidden'));
      document.querySelectorAll('button.toggle-paragraph').forEach(btn => btn.textContent = 'Mostrar Más →');

      if (!isVisible) {
        paragraph.classList.remove('hidden');
        this.textContent = 'Mostrar Menos ↑';
      } else {
        document.getElementById('servicios').scrollIntoView({ behavior: 'smooth' });
      }
    });
  });
});


const accordionElement = document.getElementById('accordion-example');

// create an array of objects with the id, trigger element (eg. button), and the content element
const accordionItems = [
    {
        id: 'accordion-example-heading-1',
        triggerEl: document.querySelector('#accordion-example-heading-1'),
        targetEl: document.querySelector('#accordion-example-body-1'),
        active: true
    },
    {
        id: 'accordion-example-heading-2',
        triggerEl: document.querySelector('#accordion-example-heading-2'),
        targetEl: document.querySelector('#accordion-example-body-2'),
        active: false
    },
    {
        id: 'accordion-example-heading-3',
        triggerEl: document.querySelector('#accordion-example-heading-3'),
        targetEl: document.querySelector('#accordion-example-body-3'),
        active: false
    }
];

// options with default values
const options = {
    alwaysOpen: true,
    activeClasses: 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white',
    inactiveClasses: 'text-gray-500 dark:text-gray-400',
    onOpen: (item) => {
        console.log('accordion item has been shown');
        console.log(item);
    },
    onClose: (item) => {
        console.log('accordion item has been hidden');
        console.log(item);
    },
    onToggle: (item) => {
        console.log('accordion item has been toggled');
        console.log(item);
    },
};

// instance options object
const instanceOptions = {
    id: 'accordion-example',
    override: true
};



    document.addEventListener("DOMContentLoaded", function () {
        const closeBtn = document.querySelector(".alert-del");
        const alertBox = document.querySelector(".alert-box");

        if (closeBtn && alertBox) {
            closeBtn.addEventListener("click", () => {
                alertBox.style.display = "none";
            });
        }
    });

/* ------arbol---------- */
