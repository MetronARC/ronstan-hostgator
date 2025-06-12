const themeToggler = document.querySelector(".theme-toggler");
const body = document.body;

// Function to apply the theme based on localStorage
function applyTheme(theme) {
    if (theme === "dark") {
        body.classList.add("dark-theme-variables");
        themeToggler.querySelector('span:nth-child(1)').classList.remove('active');
        themeToggler.querySelector('span:nth-child(2)').classList.add('active');
    } else {
        body.classList.remove("dark-theme-variables");
        themeToggler.querySelector('span:nth-child(1)').classList.add('active');
        themeToggler.querySelector('span:nth-child(2)').classList.remove('active');
    }
}

// Check localStorage and apply saved theme on page load
document.addEventListener('DOMContentLoaded', function () {
    const savedTheme = localStorage.getItem('theme') || "light"; // default to light theme
    applyTheme(savedTheme);

    // Initialize AOS on page load
    AOS.init({
        duration: 1000,
        mirror: true,
    });
});

// Toggle the theme and save it to localStorage
themeToggler.addEventListener('click', () => {
    let currentTheme = body.classList.contains("dark-theme-variables") ? "dark" : "light";
    let newTheme = currentTheme === "dark" ? "light" : "dark";
    
    applyTheme(newTheme); // Apply new theme
    localStorage.setItem("theme", newTheme); // Save new theme to localStorage
});
