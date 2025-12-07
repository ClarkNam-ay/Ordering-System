// script.js
function validateRegister() {
  const f = document.getElementById("registerForm");
  const name = document.getElementById("fullname").value.trim();
  const email = document.getElementById("email").value.trim();
  const pass = document.getElementById("password").value;
  const cpass = document.getElementById("confirm_password").value;

  if (name.length < 2) {
    alert("Enter your full name.");
    return false;
  }
  if (!email.includes("@")) {
    alert("Enter a valid email.");
    return false;
  }
  if (pass.length < 6) {
    alert("Password must be at least 6 chars.");
    return false;
  }
  if (pass !== cpass) {
    alert("Passwords do not match.");
    return false;
  }
  return true;
}

function validateLogin() {
  const email = document.getElementById("loginEmail").value.trim();
  const pass = document.getElementById("loginPassword").value;
  if (!email || !pass) {
    alert("Email and password are required.");
    return false;
  }
  return true;
}
