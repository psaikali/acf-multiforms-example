# Multi-steps form with ACF

## General concept
[Advanced Custom Fields](https://www.advancedcustomfields.com) natively handles [front-end forms](https://www.advancedcustomfields.com/resources/acf_form/), but multi-steps forms are not supported out of the box.

This proof-of-concept has been created in order to explain **how to use multiple ACF fields groups to display a multi-steps front-end form**.

Head to [the `class-shortcode.php` file](/src/class-shortcode.php) to discover the main logic behind this. This is the main file in charge of outputting our `[acf_multiforms_example]` shortcode and doing the necessary magic when processing the form with `acf/save_post`. 
It is heavily documented to explain how things work.

## Demo

### Form on the front-end
The form displayed below uses an enhanced version of `acf_form()`; it has a total of 3 steps. 

The first step is composed of two ACF fields groups, while the two other steps display only one single field group each.

_(Click the image below to see a slower video showing the final result in back-office.)_
[![Watch a detailed video](https://mosaika.fr/wip/acf-multiforms-example-front-end.gif)](http://media.mosaika.fr/5922216e1b55)

### Post data in the back-office
Nothing new here actually :) 
In the back-office, [ACF `acf_form()`](https://www.advancedcustomfields.com/resources/acf_form/) takes care of saving form data in the proper post meta fields.

Notice that the first two metaboxes below are the ones displayed in the first step of the front-end form.

![Post back-end](https://mosaika.fr/wip/acf-multiforms-example-back-end.jpg)