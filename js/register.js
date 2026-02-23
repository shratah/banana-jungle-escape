// Example: simple validation before submitting
document.getElementById('registerForm').addEventListener('submit', function(e){
    const email = document.querySelector('input[name="email"]').value;
    const password = document.querySelector('input[name="password"]').value;

    if(password.length < 6){
        alert('Password must be at least 6 characters.');
        e.preventDefault();
    }
});