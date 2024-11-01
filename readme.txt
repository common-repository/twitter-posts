=== TwitterPosts ===
Contributors: reneade
Donate link: http://www.rene-ade.de/stichwoerter/spenden
Tags: twitter, posts, update, status, text, tags, tinyurl, tweet
Stable tag: trunk
Tested up to: 2.99999

This WordPress plugin automatically sends new posts of your blog to twitter as soon as they get published. This also works with posts that are automatically published in the future. It is also possible to send all your old posts to twitter. You can define conditions which posts exactly should be send to twitter: For example you can define that all posts of a certain category or tag should be send to twitter or you can exclude them and let TwitterPosts send all others. 

== Description ==

This WordPress plugin automatically sends new posts of your blog to twitter as soon as they get published. This also works with posts that are automatically published in the future. It is also possible to send all your old posts to twitter. You can define conditions which posts exactly should be send to twitter: For example you can define that all posts of a certain category or tag should be send to twitter or you can exclude them and let TwitterPosts send all others. TwitterPosts also recognizes if you update the timestamp of a post (for example I use this if a new version is available here: than I just update the version number in the posts title and the timestamp), and twitters this post again. 
The text that will be sent to twitter of course can be customized. You can use placeholders for the title (%title%) and the link (%url%). Additionally you can use a placeholder to include the tags of your post (%tags%). Because Twitter allows only 140 characters per update, the text will be automatically cutted: First of all, of cause TinyURL is used for the posts link. The title will get cutted as far as needed if there are not enough characters left. Only if there are characters left after the text, link and title, the tags will be added as far as possible if the placeholder for the tags is used.

Available placeholders for the Twitter Status Text: %title%, %url%, %tags%.

Supported QueryVars: See http://codex.wordpress.org/Template_Tags/query_posts#Parameters ! 
cat(categoryid, multiple categorys separated with comma: 1,2 means only posts of category 1 and 2, a minus sign before the id means exclude this category: cat=-1,-2 excludes category 1 and 2),
tag(the tag name, multiple tags separated with comma means fetch posts that have either of these tags: tag1, tag2, combined with + means fetch only posts that have both tags: tag1+tag2)
posts_per_page(number), 
offset(number), 
post_type (post or page), 
post_status (publish, ...), 
orderby(date,...), 
order(ASC,DESC), 
author(authorid), 
meta_key, 
meta_value, 
... 

To send old posts to twitter: Remove the QueryVar "posts_per_page" if used before, deactivate the checkbox "Only posts newer than..." and activate "Trigger TwitterUpdate via Intervall". Than just wait until all posts have been sent to twitter. This can take a while, because we can only send a small amount of posts per hour to twitter to prevent from beeing blocked as spamer. This means for example the first 10 posts will be sent to twitter and the next 10 posts will be sent about one hour later automatically, than after another hour the next 10 posts will be sent, ... If all posts have been sent to twitter the plugin waits until there are new posts to twitter. It is recommented to set the QueryVar "posts_per_page" after all old posts have been sent. 
If you have more than 250 old posts to twitter it is recommented to split them into smaller parts by setting the "posts_per_page" QueryVar to 150 and start with QueryVar "offset" set to your number of posts minus 100 and always subtract 100 if the last 100 have bee sent completely (posts_per_page=150&offset=900, than posts_per_page=150&offset=800, than posts_per_page=150&offset=700, ... posts_per_page=150&offset=0).

Plugin Website: http://www.rene-ade.de/inhalte/wordpress-plugin-twitterposts.html
Comments are welcome! And of course, I also like presents of my Amazon-Wishlist (http://www.rene-ade.de/inhalte/amazon-wunschliste.html) or paypal donations (http://www.rene-ade.de/inhalte/paypal-spende.html). :-)

== Screenshots ==

Just visit the plugin website http://www.rene-ade.de/inhalte/wordpress-plugin-twitterposts.html

== Installation ==

1. Upload the folder 'twitter-posts' with all files to '/wp-content/plugins' on your webserver
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure TwitterPosts via the 'Settings' WordPress Adminpanel (Type in your Twitter account username and password!)
