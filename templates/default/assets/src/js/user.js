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

// region change menu image on link hover
document.querySelectorAll(".dropdown-menu-xl .dropdown-header, .dropdown-menu-xl .dropdown-item").forEach(function (item) {
    const menu = item.closest(".dropdown-menu");
    const menuImage = menu.querySelector(".dropdown-img-start");
    const menuText = menuImage.querySelector("div");
    const originMenuImage = menuImage.style.backgroundImage.slice(4, -1).replace(/"/g, "");
    const originMenuText = menuText.innerHTML;

    const itemMenuImage = item.dataset.hasOwnProperty("menu_image") ? item.dataset.menu_image : null;
    const itemMenuText = item.dataset.hasOwnProperty("menu_text") ? item.dataset.menu_text : null;

    item.addEventListener("mouseover", function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (itemMenuImage && itemMenuText) {
            menuImage.style.backgroundImage = "url(" + itemMenuImage + ")";
            menuText.innerHTML = itemMenuText;
        } else if (itemMenuImage) {
            menuImage.style.backgroundImage = "url(" + itemMenuImage + ")";
            menuText.innerHTML = "";
        } else if (itemMenuText) {
            menuImage.style.backgroundImage = null;
            menuText.innerHTML = itemMenuText;
        } else {
            if (originMenuImage) {
                menuImage.style.backgroundImage = "url(" + originMenuImage + ")";
            } else {
                menuImage.style.backgroundImage = null;
            }
            menuText.innerHTML = originMenuText;
        }
    });
});
// endregion

// region move all modal
document.querySelector("body").append(...document.querySelectorAll(".modal"));
// endregion