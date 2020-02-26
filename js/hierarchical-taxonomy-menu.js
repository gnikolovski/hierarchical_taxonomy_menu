/**
 * @file
 * Contains hierarchical-taxonomy-menu.js.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.hierarchicalTaxonomyMenuStayOpen = {
    attach: function (context, settings) {
      // Show all submenus which have list items with 'menu-item--active' class.
      if (drupalSettings.stayOpen === true) {
        $('.hierarchical-taxonomy-menu ul.menu').has('.menu-item--active').show();
      }
    }
  };

  Drupal.behaviors.hierarchicalTaxonomyMenuParentMenu = {
    attach: function (context, settings) {
      $('.menu-item.menu-item--expanded').each(function (i, obj) {
        let self = $(this);
        if (self.find('a.active').length) {
          self.addClass('active');
          if (drupalSettings.interactiveParentMenu) {
            if (!self.hasClass('menu-item--active')) {
              self.children('i').toggleClass('arrow-right arrow-down');
            }
          }
        }
      });
    }
  };

  Drupal.behaviors.hierarchicalTaxonomyMenuVisibility = {
    attach: function (context, settings) {
      if (drupalSettings.interactiveParentMenu === false) {
        $('.hierarchical-taxonomy-menu .menu-item--expanded > a').on('click', function (e) {
          e.preventDefault();
          let isChildVisible = $(this).parent().children('.menu').is(':visible');
          if (isChildVisible) {
            $(this).parent().children('.menu').slideUp();
            $(this).parent().removeClass('active');
          }
          else {
            $(this).parent().children('.menu').slideDown();
            $(this).parent().addClass('active');
          }
        });
      }
      else {
        $('.hierarchical-taxonomy-menu .menu-item--expanded > .parent-toggle').on('click', function (e) {
          e.preventDefault();
          $(this).closest('i').toggleClass('arrow-right arrow-down');
          let isChildVisible = $(this).parent().children('.menu').is(':visible');
          if (isChildVisible) {
            $(this).parent().children('.menu').slideUp();
            $(this).parent().removeClass('active');
          }
          else {
            $(this).parent().children('.menu').slideDown();
            $(this).parent().addClass('active');
          }
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
