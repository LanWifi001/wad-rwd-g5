// const body = document.querySelector('body')
// const darkBtn = document.querySelector('#toggle-dark')
// const moon = document.querySelector('#toggle-dark p')
// const darkBtn2 = document.querySelector('#toggle-dark2')
// const moon2 = document.querySelector('#toggle-dark2 #moon2')

// if (localStorage.getItem('theme') === 'dark') {
//     body.classList.add('dark')
//     moon.style.transform = 'translateX(18px)';
//     moon2.style.transform = 'translateX(18px)';
// } else {
//     moon.style.transform = 'translateX(0px)'
//     moon2.style.transform = 'translateX(0px)'
// }

// darkBtn.addEventListener('click', () => {
//     body.classList.toggle('dark')

//     // moon to right and left
//     if (body.classList.contains('dark')){
//         moon.style.transform = 'translateX(18px)'
//         moon2.style.transform = 'translateX(18px)'
//     } else {
//         moon.style.transform = 'translateX(0px)'
//         moon2.style.transform = 'translateX(0px)'
//     }

//     // Save current state
//     if (body.classList.contains("dark")) {
//         localStorage.setItem("theme", "dark");
//     } else {
//         localStorage.setItem("theme", "light");
//     }
// })
// darkBtn2.addEventListener('click', () => {
//     body.classList.toggle('dark')

//     // moon to right and left
//     if (body.classList.contains('dark')){
//         moon.style.transform = 'translateX(18px)'
//         moon2.style.transform = 'translateX(18px)'
//     } else {
//         moon.style.transform = 'translateX(0px)'
//         moon2.style.transform = 'translateX(0px)'
//     }

//     // Save current state
//     if (body.classList.contains("dark")) {
//         localStorage.setItem("theme", "dark");
//     } else {
//         localStorage.setItem("theme", "light");
//     }
// })

// // Additional for burger
// const burger = document.querySelector('.main-nav #toggle')
// const nav = document.querySelector('.main-nav')

// document.addEventListener('click', (e) => {
//     if (burger.checked && !nav.contains(e.target)) {
//         burger.checked = false
//     } 
// })

const body = document.querySelector('body')
const moon = document.querySelector('#li-dark button p')
const moonBtn = document.querySelector('#li-dark button')

if (localStorage.getItem('theme') === 'dark') {
    body.classList.add('dark')
    moon.style.transform = 'translate(10px, -2px)'
} else {
    moon.style.transform = 'translate(-10px, -2px)'
}

moonBtn.addEventListener('click', () => {
    body.classList.toggle('dark')

    if (body.classList.contains('dark')) {
        moon.style.transform = 'translate(10px, -2px)'
    } else {
        moon.style.transform = 'translate(-10px, -2px)'
    }

        // Save current state
    if (body.classList.contains("dark")) {
        localStorage.setItem("theme", "dark");
    } else {
        localStorage.setItem("theme", "light");
    }
})