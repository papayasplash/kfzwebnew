jQuery(document).ready(function() {
jQuery('.slider-main').slick({
  slidesToShow: 1,
  slidesToScroll: 1,
  arrows: false,
  fade: true,
  asNavFor: '.slider-nav'
});
jQuery('.slider-nav').slick({
  slidesToShow: 3,
  slidesToScroll: 1,
  asNavFor: '.slider-main',
  dots: false,
  arrows: true,
  centerMode: true,
  focusOnSelect: true
});

});