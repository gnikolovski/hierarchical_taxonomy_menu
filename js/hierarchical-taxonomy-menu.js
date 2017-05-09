(function ($) {
  'use strict';
  $(document).ready(function () {
    if (drupalSettings.collapsibleMenu === 0) {
      return;
    }

    $('.hierarchical-taxonomy-menu ul.menu').not(':has(.menu-item--active)').hide();

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
  });
})(jQuery);
