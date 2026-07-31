function toggleMenu(element) {
    const currentSubmenu = element.nextElementSibling;
    const currentArrow = element.querySelector(".arrow");

    const isOpen = currentSubmenu.classList.contains("open");
    document.querySelectorAll(".submenu").forEach(submenu => {
        submenu.classList.remove("open");
    });

    document.querySelectorAll(".arrow").forEach(arrow => {
        arrow.classList.remove("rotate");
    });

    document.querySelectorAll(".menu-header").forEach(header => {
        header.classList.remove("open");
    });

    if (!isOpen) {
        currentSubmenu.classList.add("open");
        currentArrow.classList.add("rotate");
        element.classList.add("open");
    }
}