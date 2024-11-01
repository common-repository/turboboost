var hasViewportMetaTag = document.querySelector('meta[name="viewport"]');
if (!hasViewportMetaTag) {
    // If viewport meta tag is not present, add it
    var viewportMetaTag = document.createElement('meta');
    viewportMetaTag.name = 'viewport';
    viewportMetaTag.content = 'width=device-width, initial-scale=1.0';
    document.head.appendChild(viewportMetaTag);
}
// Get all images on the page
var images = document.querySelectorAll('img');

// Iterate through each image
images.forEach(function (image) {
    // Check if the image has an alt attribute
    if (!image.hasAttribute('alt')) {
        // If not, set alt attribute to the image name (assuming the image has a name in the src)
        var imageName = getImageName(image.src);
        image.alt = imageName;
    }
});

// Function to extract the image name from the src
function getImageName(src) {
    var index = src.lastIndexOf('/');
    var imageName = src.substring(index + 1);
    // Remove file extension if present
    imageName = imageName.split('.')[0];
    // Replace any underscores with spaces (you can modify this based on your naming conventions)
    imageName = imageName.replace(/_/g, ' ');
    return imageName;
}