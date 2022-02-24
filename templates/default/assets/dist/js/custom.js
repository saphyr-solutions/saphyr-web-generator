document.addEventListener('scroll', function (e) {
    var $nav = document.querySelector('.navbar.fixed-top');
    if(document.documentElement.scrollTop > $nav.clientHeight) {

        if (!$nav.classList.contains('scrolled')) $nav.classList.add('scrolled');
    } else {
        if ($nav.classList.contains('scrolled')) $nav.classList.remove('scrolled');
    }
});


var FormElements = document.querySelectorAll('form');
if (FormElements.length) {
    FormElements.forEach(function (item) {

        item.addEventListener('submit', (event) => {
            /**
             * @todo Check submitted fields before send
             *
             */

            event.stopPropagation();
            event.preventDefault();

                fetch('/', {
                    method: 'POST',
                    body: new FormData(item),
                })
                    .then(res => res.json())
                    .then(res => {
                        console.log(res);
                        if (res.confirm_message && res.result=="success") {
                            alert(res.confirm_message);
                            event.target.reset();
                        } else {
                            alert("Erreur lors de l'envoi des informations")
                        }
                        }
                    )
                    .catch(err => console.error(err));
                item.dataset.submitted=true;

            return false;

        });

    });
}
