

/*=============== SWIPER IMAGES ===============*/
const swiperLogin = new Swiper('.login__swiper', {
   loop: true,
   spaceBetween: '24',
   grabCursor: true,
   speed: 600,
   // effect: 'fade',

   pagination: {
      el: '.swiper-pagination',
      clickable: true,
   },

   autoplay: {
      delay: 3000,
      disableOnInteraction: false,
   },
})
