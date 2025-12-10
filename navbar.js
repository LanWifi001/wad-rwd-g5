const togglebtn = document.querySelector('.navbar-btn');
const navbar = document.querySelector('#navbar');
const aside = document.querySelector('#sidebar');
const navcollapsed = document.querySelectorAll('.navbar-collapsed');
const hidden = document.getElementById('hidden-collapse')
const main = document.querySelector('#main')

// On page load, restore state
if (localStorage.getItem('navbar-collapsed') === 'true') {
    navbar.classList.add('toggle');
    aside.classList.add('toggle-aside');
    navcollapsed.forEach(item => item.classList.add('toggle-collapsed'));
    hidden.classList.toggle('navbar-collapsed')

    main.classList.toggle('main-collapsed')
}

togglebtn.addEventListener('click', () => {
    navbar.classList.toggle('toggle');
    aside.classList.toggle('toggle-aside');

    navcollapsed.forEach(item => {
        item.classList.toggle('toggle-collapsed');
    });

    hidden.classList.toggle('navbar-collapsed')

    main.classList.toggle('main-collapsed')

    // Save state to localStorage
    localStorage.setItem('navbar-collapsed', navbar.classList.contains('toggle'));
});
