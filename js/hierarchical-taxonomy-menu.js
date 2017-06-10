(function ($) {
  'use strict';
  $(document).ready(function () {
    if (drupalSettings.collapsibleMenu === 0) {
      return;
    }

    $('.hierarchical-taxonomy-menu ul.menu').not(':has(.menu-item--active)').hide();

    $('.menu-item.menu-item--expanded').each(function(i, obj) {
      var self = $(this);
      if (self.find('a.active').length) {
        self.addClass('active');
      }
    });

    if (drupalSettings.interactiveParentMenu === 0) {
      $('.hierarchical-taxonomy-menu .menu-item--expanded > a').on('click', function (e) {
        e.preventDefault();
        var isChildVisible = $(this).parent().children('.menu').is(':visible');
        if (isChildVisible) {
          $(this).parent().children('.menu').slideUp();
          $(this).parent().removeClass('active');
        }
        else {
          $(this).parent().children('.menu').slideDown();
          $(this).parent().addClass('active');
        }
      });
    } else {
      $('.hierarchical-taxonomy-menu .menu-item--expanded > .parent-toggle').on('click', function (e) {
        e.preventDefault();
        $(this).closest('i').toggleClass("fa-arrow-circle-right fa-arrow-circle-down");
        var isChildVisible = $(this).parent().children('.menu').is(':visible');
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
