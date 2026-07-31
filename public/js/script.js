function toggleMenu(element) {
    const submenu = element.nextElementSibling;
    const arrow = element.querySelector(".arrow");

    if (submenu.classList.contains("open")) {
        submenu.classList.remove("open");
        arrow.classList.remove("rotate");
        element.classList.remove("open");
    } else {
        submenu.classList.add("open");
        arrow.classList.add("rotate");
        element.classList.add("open");
    }
}