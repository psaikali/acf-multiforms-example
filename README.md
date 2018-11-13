# Multi-steps form with ACF

## General concept
[Advanced Custom Fields](https://www.advancedcustomfields.com) natively handles [front-end forms](https://www.advancedcustomfields.com/resources/acf_form/), but multi-steps forms are not an option by default.

This proof-of-concept has been created in order to explain **how to use multiple ACF fields groups to display a multi-steps front-end form**.

## Demo

### Front-end form
The form displayed below displays a total of 3 steps. 

The first step is composed of two ACF fields groups, while the two other steps display only one single field group each.


_(Click the image below to see a slower video showing the final result in back-office.)_
[![Watch a detailed video](https://mosaika.fr/wip/acf-multiforms-example-front-end.gif)](http://media.mosaika.fr/5922216e1b55)

### Post back-office
In the back-office, [ACF `acf_form()`](https://www.advancedcustomfields.com/resources/acf_form/) takes care of saving form data in the proper post meta fields. Nothing new here :)

Notice that the first two metaboxes below are the ones displayed in the first step of the front-end form.

![Post back-end](https://mosaika.fr/wip/acf-multiforms-example-back-end.jpg)

## Technical details
_todo_