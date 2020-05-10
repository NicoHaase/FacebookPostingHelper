# FacebookPostingHelper

Posting to a page is easy - as long as you start your browser and write the post there. Doing it through the Facebook API is a bit more complicated, as you have to use an app to push posts to Facebook. This tiny class helps you to receive the proper configuration and ease the process of automatic posting.

# Requirements
 - You need a Facebook account that has admin permissions on the page you want to post to
 -  Save for later: the ID of the Facebook page you want to post to, for example through http://findmyfacebookid.com/
 - Register an app that will write on your wall through https://developers.facebook.com/
  - Choose the type "Website"
  - Save for later: App ID and App Secret
 - Create an option file to keep your configuration (see example/options.ini for an example file), make it writeable through your web server
 - Get the package through composer, dependency:

  ````"nicohaase/facebookpostinghelper": "^1.0"````
  
- Write your code around the FacebookPostingHelper
  - You need to perform the authorization in your browser (see example/performLogin.php), as fiddling around with tokens is confusing!
  - The page access token, needed for the helper to put posts on your page, does not expire according to Facebook (paste it to https://developers.facebook.com/tools/debug/ to see its power)
  - Posting is possible afterwards through only little lines (see example/writePost.php)