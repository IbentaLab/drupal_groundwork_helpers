// Updated devtools-bar.js with hardened Clear Cache functionality
(function (Drupal) {
  Drupal.behaviors.gwDevtoolsBar = {
    attach: function (context, settings) {
      const bar = document.getElementById("gw-devtools-bar");
      const minBtn = document.getElementById("gw-devtools-minimized");
      if (!bar) return;

      // --- Handle toolbar state ---
      const state = localStorage.getItem("gwDevtoolsState") || bar.getAttribute("data-default-state") || "expanded";

      function showBar() {
        bar.style.display = "";
        bar.classList.add("gw-devtools-expanded");
        bar.classList.remove("gw-devtools-minimized");
        if (minBtn) minBtn.style.display = "none";
        localStorage.setItem("gwDevtoolsState", "expanded");
      }

      function hideBar() {
        bar.style.display = "none";
        if (minBtn) minBtn.style.display = "";
        localStorage.setItem("gwDevtoolsState", "minimized");
      }

      if (state === "minimized") hideBar();
      else showBar();

      const toggle = bar.querySelector(".gw-devtools-toggle");
      if (toggle) toggle.addEventListener("click", hideBar);
      if (minBtn) minBtn.addEventListener("click", showBar);

      // --- Handle theme switching ---
      function setTheme(theme) {
        const html = document.documentElement;
        if (theme === "dark") {
          html.setAttribute("data-theme", "dark");
          localStorage.setItem("gwThemeMode", "dark");
        } else if (theme === "light") {
          html.setAttribute("data-theme", "light");
          localStorage.setItem("gwThemeMode", "light");
        } else {
          html.removeAttribute("data-theme");
          localStorage.removeItem("gwThemeMode");
        }
      }

      const savedTheme = localStorage.getItem("gwThemeMode");
      if (savedTheme === "dark" || savedTheme === "light") {
        setTheme(savedTheme);
      }

      function toggleTheme() {
        const html = document.documentElement;
        const current = html.getAttribute("data-theme") || localStorage.getItem("gwThemeMode") || "";
        setTheme(current === "dark" ? "light" : "dark");
      }

      // --- Action buttons ---
      const toolBtns = bar.querySelectorAll(".gw-devtools-btn");
      toolBtns.forEach(function (btn) {
        btn.addEventListener("click", function () {
          const action = btn.getAttribute("data-action");

          switch (action) {
            case "toggle-theme":
              toggleTheme();
              break;

            case "clear-caches": {
              const originalText = btn.textContent;
              btn.disabled = true;
              btn.textContent = "Clearing...";

              fetch("/groundwork-devtools/clear-caches", {
                method: "GET",
                credentials: "same-origin",
                headers: { "X-Requested-With": "XMLHttpRequest" },
              })
                .then((res) => {
                  if (!res.ok) throw new Error(`HTTP ${res.status}`);
                  return res.json();
                })
                .then((data) => {
                  if (data.status) {
                    showGwNotice("✅ All caches cleared!", "success");
                  } else {
                    showGwNotice(`⚠️ ${data.error || "Error clearing caches"}`, "error");
                  }
                })
                .catch((err) => {
                  console.error("Error during cache clear:", err);
                  showGwNotice("❌ Failed to clear caches.", "error");
                })
                .finally(() => {
                  btn.disabled = false;
                  btn.textContent = originalText;
                });
              break;
            }
          }
        });
      });

      function showGwNotice(message, type = "info") {
        const msg = document.createElement("div");
        msg.className = `gw-devtools-notice gw-devtools-notice--${type}`;
        msg.textContent = message;
        msg.setAttribute("role", "status");

        document.body.appendChild(msg);

        setTimeout(() => {
          msg.classList.add("gw-devtools-notice--fade");
          msg.addEventListener("transitionend", () => msg.remove());
        }, 3000);
      }
    },
  };
})(Drupal);
