jQuery(document).ready(function() {
jQuery('.kfz-slider-main').slick({
  slidesToShow: 1,
  slidesToScroll: 1,
  arrows: false,
  fade: true,
  asNavFor: '.kfz-slider-nav'
});
jQuery('.kfz-slider-nav').slick({
  slidesToShow: 3,
  slidesToScroll: 1,
  asNavFor: '.kfz-slider-main',
  dots: true,
  arrows: true,
  centerMode: true,
  focusOnSelect: true
});

});