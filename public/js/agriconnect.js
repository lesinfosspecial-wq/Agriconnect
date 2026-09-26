document.querySelectorAll("[data-telephone]").forEach((button) => {
    button.addEventListener("click", () => {
        const form = button.closest("form");
        if (!form) return;
        const phone = form.querySelector('input[name="telephone"]');
        const password = form.querySelector('input[name="password"]');
        if (phone) phone.value = button.dataset.telephone;
        if (password) password.value = button.dataset.password;
        form.requestSubmit();
    });
});

document.querySelector("[data-theme-toggle]")?.addEventListener("click", () => {
    document.body.classList.toggle("is-dark");
});

const dash = document.querySelector("#dash");
const dashToggle = document.querySelector("[data-dash-toggle]");
dashToggle?.addEventListener("click", () => {
    const open = dash.classList.toggle("is-open");
    dashToggle.setAttribute("aria-expanded", open ? "true" : "false");
});
dash?.querySelectorAll(".dash-nav a").forEach((link) => {
    link.addEventListener("click", () => dash.classList.remove("is-open"));
});
const nav = document.querySelector(".hnav");
const navToggle = document.querySelector("[data-nav-toggle]");
navToggle?.addEventListener("click", () => {
    const open = nav.classList.toggle("is-open");
    navToggle.setAttribute("aria-expanded", open ? "true" : "false");
});
nav?.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => nav.classList.remove("is-open"));
});
