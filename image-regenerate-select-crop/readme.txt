=== Image Regenerate & Select Crop ===
Contributors: Iulia Cazan
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ
Tags: image optimization, crop, regenerate, cleanup, bulk regenerate
Requires at least: 4.9.2
Tested up to: 6.6
Stable tag: 8.0.3
Requires PHP: 7.3.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced management for images, register new sub-sizes, sub-sizes details, regenerate and cleanup files.

== Description ==
Image Regenerate & Select Crop plugin provides enhanced control over image settings and files regeneration, allowing you to override native medium and large crop options, and register new sub-sizes. The plugin appends additional buttons to regenerate and crop images (providing detailed information about the registered image sub-sizes and their status within the application) and enables configuration of the plugin for global or specific post-type attached images.
For the latest updates and detailed feature descriptions, visit [https://iuliacazan.ro/image-regenerate-select-crop/](https://iuliacazan.ro/image-regenerate-select-crop/).


= Key Features =
* Details button: opens a lightbox displaying detailed information on any missing sub-size files and options for manual generation if applicable. For crop-type images, the plugin offers the one-click re-crop option, using specific portions of the original image with an instant preview feature.
* Regenerate button: allows one-click regeneration of all image sizes for a specific image, ensuring that all uploaded images are updated with the newly registered sizes.
* Raw cleanup button: allows you to delete all files and metadata for the attachment, keeping only the original file and information, so you can easily regenerate afterward only the desired sub-sizes.
* The plugin integrates seamlessly without requiring additional code, adding the buttons to the "Edit Media" page, as well as "Edit Post" and "Edit Page" sections where a featured image is present, and in the image block. It is compatible with custom post types (including WooCommerce products) and supports various resolutions and responsive layouts.


== Screenshots ==
1. The most recent view of the image info, with details and links for the original file and all the generated images.
2. Extra details about the registered and not registered image sizes and all the generated files with the option to delete individual files. The extra info is available in the modal, at the bottom of the list.
3. Example of advanced custom rules based on the posts where the images will be uploaded (and how to temporarily suppress the rules without removing these).
4. The general setting view, placeholders, cron taks etc.
5. Example of settings that override the crop option for native sub-sizes, create/remove custom image sizes registered with the plugin.
6. Example of the plugin buttons in the media listing view for each of the attachments, to allow direct access to details, bulk regenerate all sub-sizes of the attachment and cleanup. Additionally, the summary of files is displayed (turned on/off from settings)
7. Example of the plugin buttons for the featured image of a post.
8. Example of the plugin buttons for the WooCommerce product featured image and the product gallery.
9. The general setting view with options to regenerate all images for a specific size, cleanup, general crop position, quality, globally ignore sub-sizes, hide sub-sizes from views.


== Changelog ==
= 8.0.3 =
* Attempt to fix the SVG dimensions when the core feature fails on upload (when SSL operation failed due to routines:tls_process_server_certificate:certificate verify)
* Fixed cron schedule warning for custom frequency

See the [changelog](changelog.txt) for detailed information on changes made in the earlier versions.

== License ==
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.


== Frequently Asked Questions ==

= How to use the plugin wp-cli commands? =
See details and examples at [https://iuliacazan.ro/image-regenerate-select-crop/#wp-cli-faq](https://iuliacazan.ro/image-regenerate-select-crop/#wp-cli-faq)

= Can I use the buttons in my code? =
If you want to display the custom buttons in your plugins, you can use the custom action with $attachmentId parameter as the image post->ID you want the button for. Usage example : do_action( 'image_regenerate_select_crop_button', $attachmentId );

= What is the image placeholder mode? =
This option allows you to display placeholders for front-side images called programmatically (that are not embedded in content with their src, but retrieved with the wp_get_attachment_image_src, and the other related WP native functions). If there is no placeholder set, then the default behavior would be to display the full size image instead of the missing image size. If you activate the "force global" option, all the images on the front side that are related to posts will be replaced with the placeholders that mention the image size required. This is useful for debugging, to quickly identify the image sizes used for each layout. If you activate the "only missing images" option, all the images on the front side that are related to posts and do not have the requested image size generated will be replaced with the placeholders that mention the image size required. This is useful for showing smaller images instead of full-size images.

= How to ignore globally a sub-size? =
You can exclude globally from the application some of the sub-sizes that are registered through various plugins and themes' options, but you don't need these in your application at all (these are just stored in your folders and database but not used). By excluding these, unnecessary image sizes will not be generated at all.

= What does force original do? =
This option means that the original image will be scaled to a max width or a max height specified by the image size you select. This might be useful if you do not use the original image in any of the layouts at the full size, and this might save some storage space. Leave "nothing selected" to keep the original image as what you upload.

= When to use the cleanup all option? =
This option allows you to clean up all the image sizes you already have in the application but you don't use these at all. Please be careful, once you click to remove the selected image size, and the action is irreversible, the images generated will be deleted from your folders and database records.

= When to use the regenerate all option? =
This option allows you to regenerate all the images for the selected sub-sizes. Please be careful, once you click to regenerate the selected image size, the action is irreversible, and the images already generated will be overwritten.

= What is the default crop? =
This option allows you to set a default crop position for the images generated for a particular image sub-size. This default option will be used when you chose to regenerate an individual image or all of these and also when a new image is uploaded.


== Demo ==
https://youtu.be/3hRSXMx3dcU
