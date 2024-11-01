<div class="container turbo-ui turbo-dashboard">
<div class=turbo-info-sec>
    <h1>Welcome to Turboboost for WordPress!</h1>
    <h5 class="turbo-connect-subtitle" >Follow these simple steps to connect your WordPress website to <b>Turboboost</b>.</h5>
    <div class="turbo-image-box">
        <img src= "<?php echo PLUGIN_IMAGE_URL . 'turboboost_icon-256-256.png' ?>" alt="tubologo">
    </div>
</div>
<div class="turbo-step-initial">
    <div class="initial-content">
        <h1>Let's Get Started</h1>
        <h5  class="turbo-connect-subtitle">To connect Turboboost with WordPress, simply click the button and sign up for an account.</h5>
    </div>
    <div id="myDiv"  class="turbo-connect-button">
        <button id="connectTurboboost">Connect to Turboboost</button>
    </div>
</div>
</div>

<script>
(function($) {
    let turboboostConnectWindow = null;
    let homePageUrl = "<?php echo esc_url(get_home_url()); ?>";
    let adminEmail = "<?php echo esc_html(get_option('admin_email')); ?>";
    let siteName = "<?php echo esc_html(get_option('blogname')); ?>";
    let callbackUrl = homePageUrl + '/wp-json/turboboost/v1/subscription-callback';
    $("#connectTurboboost").on("click", function(e) {
        e.preventDefault();
    let screenWidth = window.screen.availWidth;
    let screenHeight = window.screen.availHeight;
    let windowWidth = 500;
    let windowHeight = 700;
    let leftPos = window.top.outerWidth / 2 + window.top.screenX - ( windowWidth / 2);
    let topPos = window.top.outerHeight / 2 + window.top.screenY - ( windowHeight / 2);
    turboboostConnectWindow = window.open("https://dashboard-dev.turbo-boost.io/auth-integration?siteUrl=" + homePageUrl +"&email=" + adminEmail +"&siteName=" + siteName, "turboboostDashboard", "width=" + windowWidth + ",height=" + windowHeight + ",left=" + leftPos + ",top=" + topPos);

    // turboboostConnectWindow = window.open("http://localhost:3000/auth-integration?siteUrl=" + homePageUrl +"&email=" + adminEmail +"&siteName=" + siteName, "turboboostDashboard", "width=" + windowWidth + ",height=" + windowHeight + ",left=" + leftPos + ",top=" + topPos);

        
       // Track the popup window
       var checkPopupInterval = setInterval(function(){
                // Check if popup window is closed
                if (turboboostConnectWindow.closed) {
                    clearInterval(checkPopupInterval);
                } else {
                    // Check if popup window URL contains token query string parameter
                    if (turboboostConnectWindow.location.search.indexOf('token') !== -1) {
                        const urlParams = new URLSearchParams(turboboostConnectWindow.location.search);
                        const tokenValue = urlParams.get('token');
                        if(tokenValue) {
                            jQuery.post(ajaxurl, {
                                action: 'update_token',
                                token: tokenValue
                            }, function(response) {
                                // Handle the response
                                turboboostConnectWindow.close();
                                location.reload(); // Close the popup window
                                clearInterval(checkPopupInterval);
                                console.log("done");
                            });
                           
                       
                        }
                    }
                }
            }, 5000);


});
function getParameterByName(name, url) {
    if (!url) url = turboboostConnectWindow.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
})(jQuery);
</script>