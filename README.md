# Hierarchical Taxonomy Menu

## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Author

## INTRODUCTION

Hierarchical Taxonomy Menu is a Drupal 8 module for creating menus from taxonomy
terms. You can display an image next to a menu item if your terms have an image 
field, and there is also an option to make menu collapsible. This module comes 
with a Twig template, so you can customize HTML structure any way you want.

## REQUIREMENTS

None.

## INSTALLATION

1. Install module as usual via Drupal UI, Drush or Composer
2. Go to "Extend" and enable the Hierarchical Taxonomy Menu module.

## CONFIGURATION

After you install the module go to the block layout '/admin/structure/block' and
place a 'Hierarchical Taxonomy Menu' block to any region you want. In block 
settings you can choose a vocabulary from which you want to create a menu, and 
if that vocabulary has image fields you will see multiple options in select box. 
You can limit your menu to a part of taxonomy terms, by selecting a base term. 
In this case menu items will be generated only for its children terms.

### AUTHOR

Goran Nikolovski  
Website: http://gorannikolovski.com   
Drupal.org: https://www.drupal.org/u/gnikolovski   
Email: nikolovski84@gmail.com   

Company: Studio Present, Subotica, Serbia   
Website: http://www.studiopresent.com   
Drupal: https://www.drupal.org/studio-present   
Email: info@studiopresent.com
