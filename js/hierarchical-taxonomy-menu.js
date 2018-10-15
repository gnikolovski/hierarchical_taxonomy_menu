/**
 * @file
 * Contains hierarchical-taxonomy-menu.js.
 */

(function ($) {
  'use strict';
  $(document).ready(function () {
    if (drupalSettings.collapsibleMenu === false) {
      $('.hierarchical-taxonomy-menu .menu').css('display', 'block');
      return;
    }

    $('.hierarchical-taxonomy-menu ul.menu').not(':has(.menu-item--active)').hide();

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
  });
})(jQuery);
