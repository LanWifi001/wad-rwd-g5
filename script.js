const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const signUpButton2 = document.getElementById('signUp2');
const signInButton2 = document.getElementById('signIn2');
const container = document.getElementById('container');

// Switch panels
signUpButton.addEventListener('click', () => {
    container.classList.add("right-panel-active");
});

signInButton.addEventListener('click', () => {
    container.classList.remove("right-panel-active");
});