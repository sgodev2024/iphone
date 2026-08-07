(function (window) {
    const positions = [
        "top-right",
        "top-center",
        "top-left",
        "bottom-right",
        "bottom-center",
        "bottom-left",
    ];

    function getContainer(position) {
        if (!positions.includes(position)) position = "top-center";
        const id = "toastr-container-" + position;
        let container = document.getElementById(id);
        if (!container) {
            container = document.createElement("div");
            container.id = id;
            document.body.appendChild(container);
        }
        return container;
    }

    const toastr = {
        show(message, type = "info", options = {}) {
            const delay = options.time || 5000;
            const position = options.position || "top-center";

            const container = getContainer(position);

            const toast = document.createElement("div");
            toast.classList.add("toastr", type);

            // Thêm class show-top hoặc show-bottom tùy vị trí để chạy animation đúng
            if (position.startsWith("top")) {
                toast.classList.add("show-top");
            } else {
                toast.classList.add("show-bottom");
            }

            const iconMap = {
                success: '<i class="fa-solid fa-circle-check"></i>',
                error: '<i class="fa-solid fa-circle-exclamation"></i>',
                warning: '<i class="fa-solid fa-triangle-exclamation"></i>',
                info: '<i class="fa-solid fa-circle-info"></i>',
            };

            toast.innerHTML = `
            <span class="icon">${iconMap[type]}</span>
            <span>${message}</span>
            <span class="close-btn"><i class="fa-solid fa-xmark"></i></span>
            <div class="progress-bar"></div>
        `;

            // Thêm animation cho progress-bar
            const progressBar = toast.querySelector(".progress-bar");
            progressBar.style.animation = `slideOut ${delay}ms linear forwards`;

            // Animation kết thúc thì xóa phần tử
            toast.addEventListener("animationend", (e) => {
                if (
                    e.animationName === "fadeUpOut" ||
                    e.animationName === "fadeDownOut"
                ) {
                    if (container.contains(toast)) container.removeChild(toast);
                }
            });

            // Nút đóng
            toast.querySelector(".close-btn").onclick = () => {
                if (position.startsWith("top")) {
                    toast.classList.remove("show-top");
                    toast.classList.add("hide-up");
                } else {
                    toast.classList.remove("show-bottom");
                    toast.classList.add("hide-down");
                }
            };

            container.appendChild(toast);

            // Tự động ẩn sau khoảng thời gian delay
            setTimeout(() => {
                if (!container.contains(toast)) return;
                if (position.startsWith("top")) {
                    toast.classList.remove("show-top");
                    toast.classList.add("hide-up");
                } else {
                    toast.classList.remove("show-bottom");
                    toast.classList.add("hide-down");
                }
            }, delay);
        },

        success(msg, opts) {
            this.show(msg, "success", opts);
        },
        error(msg, opts) {
            this.show(msg, "error", opts);
        },
        warning(msg, opts) {
            this.show(msg, "warning", opts);
        },
        info(msg, opts) {
            this.show(msg, "info", opts);
        },
    };

    window.datgin = toastr;
})(window);
