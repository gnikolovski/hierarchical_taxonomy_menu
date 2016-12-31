#Hierarchical taxonomy menu

##CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Using the module
  * Author

##INTRODUCTION

Hierarchical taxonomy menu is a simple Drupal 8 module for creating menus with
taxonomy vocabularies. The module is multilingual, and comes with a Twig
template, so you can customize HTML structure the way you want. You can also
display an image next to a menu item if your vocabulary has an image field. Just
select the field from which you want to take an image. If you are on one of the
taxonomy term routes a corresponding menu item will get a class with a name
'active'.

##REQUIREMENTS

None.

##INSTALLATION

1. Install module as usual via Drush or Drupal UI
2. Go to "Extend" and enable the Hierarchical taxonomy menu module.

##CONFIGURATION

After you install the module go to
'admin/config/user-interface/hierarchical_taxonomy_menu' and select the
vocabulary you want to use to create a menu. You can also choose an image field
and set image dimensions.

##USING THE MODULE

Go to the block layout 'admin/structure/block' and add
'Hierarchical taxonomy menu' block to any region you want.

###AUTHOR

Goran Nikolovski
Website: (http://www.gorannikolovski.com)
Drupal: (https://www.drupal.org/user/3451979)
Email: nikolovski84@gmail.com

Company: Studio Present, Subotica, Serbia
Website: (http://www.studiopresent.com)
Drupal: (https://www.drupal.org/studio-present)
Email: info@studiopresent.com
