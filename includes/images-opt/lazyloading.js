    // Function to check if an img tag has loading attribute set to "lazy"
function checkLazyLoading() {
    const imgTags = document.querySelectorAll('img'); // Select all img elements

    imgTags.forEach(img => {
        if (img.hasAttribute('loading') && img.getAttribute('loading').toLowerCase() === 'lazy') {
        } else {
            img.setAttribute("loading", "lazy");
        }
    });
}

// Call the function when the page is fully loaded
document.addEventListener('DOMContentLoaded', checkLazyLoading);
