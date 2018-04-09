<style>
    .fb-login-button.fb_iframe_widget{
        margin-top: 10px;
    }
</style>
<script>
    $(document).ready(function () {
        window.fbAsyncInit = function () {
            FB.init({
                appId: '1494387174021393',
                cookie: true,
                xfbml: true,
                version: 'v2.12'
            });

            FB.AppEvents.logPageView();

            FB.getLoginStatus(function (response) {
                statusChangeCallback(response);
            });
        };

        (function (d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) {
                return;
            }
            js = d.createElement(s);
            js.id = id;
            js.src = "https://connect.facebook.net/en_US/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));

        function checkLoginState() {
            FB.getLoginStatus(function (response) {
                statusChangeCallback(response);
            });
        }

        // This is called with the results from from FB.getLoginStatus().
        function statusChangeCallback(response) {
            console.log(response);
            // The response object is returned with a status field that lets the
            // app know the current login status of the person.
            // Full docs on the response object can be found in the documentation
            // for FB.getLoginStatus().
            if (response.status === 'connected') {
                // Logged into your app and Facebook.
                get_userInfo();
            } else {
                location.href = BASE + 'auth/';
            }
        }

        function get_userInfo() {
            FB.api('/me?fields=id,email,first_name,last_name,name', function (response) {
                console.log(response)
                $.post(BASE + 'auth/doFbLogin', {userdata: response}, function (res) {
                    window.location.href = BASE;
                }, 'json');
            });
        }
    });
</script>
<div class="reg-now">
    <h2 class='medium-h text-center'>Take part in the training</h2>
    <h3 class='xsmall-h text-center'>Nulla ornare tortor quis rhoncus vulputate. </h3>
    <form class='reg-now-visible' id='formIndex' method=post >
        <div class='control-group'>
            <input type="text" name="fname" id="fname" placeholder='Enter your First Name' value="" data-required>
        </div>
        <div class='control-group'>
            <input type="text" name="lname" id="lname" placeholder='Enter your Last Name' value="" data-required data-pattern="^[-a-z0-9!#$%&'*+/=?^_`{|}~]+(\.[-a-z0-9!#$%&'*+/=?^_`{|}~]+)*@([a-z0-9]([-a-z0-9]{0,61}[a-z0-9])?\.)*(aero|arpa|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|[a-z][a-z])$">
        </div>
        <div class='control-group'>
            <input type="text" name="email" id="email" placeholder='Enter your Email Address' value="" data-required data-pattern="^[0-9]+$">
        </div>

        <div class="filter">
            <select name="input_name[3]">
                <option value="Select Your Caoch">Select Your Caoch</option>
                <option value="Caoch 1">Caoch 1</option>
                <option value="Caoch 2">Caoch 2</option>
                <option value="Caoch 3">Caoch 3</option>
            </select>
        </div>
        <button type="submit" value="Register Now" class='btn submit' name="submit"><i class="icon-success"></i>Register Now</button>
        <div class="fb-login-button" data-width="300" data-size="large" data-button-type="login_with" data-show-faces="false" data-auto-logout-link="false" data-use-continue-as="true"></div>
    </form>
</div>