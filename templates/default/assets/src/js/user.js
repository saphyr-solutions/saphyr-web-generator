// 
// user.js
// Use this to write your custom JS
//

// region add scrolled class on navbar
document.addEventListener('scroll', function (e) {
    var $nav = document.querySelector('.navbar.fixed-top');
    if (document.documentElement.scrollTop > $nav.clientHeight) {
        if (!$nav.classList.contains('scrolled')) $nav.classList.add('scrolled');
    } else {
        if ($nav.classList.contains('scrolled')) $nav.classList.remove('scrolled');
    }
});
// endregion

// region submit form
document.querySelectorAll('form:not([action])').forEach(function (item) {
    item.addEventListener('submit', function (event) {
        event.stopPropagation();
        event.preventDefault();

        fetch('/', {
            method: 'POST',
            body: new FormData(item),
        }).then(function (res) {
            return res.json();
        }).then(function (res) {
            const successMessage = item.dataset.succesMessage;
            const redirect = item.dataset.redirect;
            if (res.hasOwnProperty("error")) {
                alert(res.error.message);
            } else {
                if (successMessage && redirect) {
                    item.innerHTML = `<div class="d-grid"><a href="${redirect}" class="btn btn-success">${successMessage}</a></div>`;
                } else if (successMessage) {
                    item.innerHTML = `<div class="alert alert-success"><h4 class="alert-heading">${successMessage}</h4></div>`;
                } else if (redirect) {
                    window.location.replace(redirect);
                } else {
                    item.innerHTML = `<div class="alert alert-success"><h4 class="alert-heading">Merci !</h4></div>`;
                }
            }
        });
        return false;
    });
});
// endregion

// region bind click on .select_card
window.select_card_values = {};
document.querySelectorAll('[data-select_card_checkbox]').forEach(function (item) {
    item.addEventListener('change', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const key = item.dataset["select_card_checkbox"];

        if (event.currentTarget.checked) {
            window.select_card_values[key] = true;
        } else {
            delete window.select_card_values[key];
        }
        document.dispatchEvent(new Event("select_card_change"));
    });
});

document.querySelectorAll('.select_card').forEach(function (item) {
    item.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        const checkbox = item.querySelector("[data-select_card_checkbox]");
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event("change"));
    });
});
// endregion