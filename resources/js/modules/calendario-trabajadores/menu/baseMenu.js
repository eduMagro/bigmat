let current = null;

export function closeMenu() {
    if (!current) return;
    current.remove();
    current = null;
    document.removeEventListener("click", closeMenu);
    document.removeEventListener("scroll", closeMenu, true);
    window.removeEventListener("resize", closeMenu);
}

export function openMenuAt(x, y, html) {
    closeMenu();
    const el = document.createElement("div");
    el.className = "fc-contextmenu";
    Object.assign(el.style, {
        position: "fixed",
        zIndex: 9999,
        minWidth: "240px",
        background: "#fff",
        border: "1px solid #e5e7eb",
        boxShadow:
            "0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",
        borderRadius: "8px",
        overflow: "hidden",
        visibility: "hidden", // Ocultar temporalmente para medir
    });
    el.innerHTML = html;
    document.body.appendChild(el);

    // Medir el menú
    const menuRect = el.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;

    // Determinar posición vertical (arriba o abajo)
    let finalY = y;
    if (y + menuRect.height > viewportHeight) {
        // No cabe abajo, colocar arriba
        finalY = y - menuRect.height;
        // Si tampoco cabe arriba, ajustar al borde superior
        if (finalY < 0) {
            finalY = Math.max(0, viewportHeight - menuRect.height);
        }
    }

    // Determinar posición horizontal
    let finalX = x;
    if (x + menuRect.width > viewportWidth) {
        // No cabe a la derecha, colocar a la izquierda
        finalX = x - menuRect.width;
        // Si tampoco cabe a la izquierda, ajustar al borde derecho
        if (finalX < 0) {
            finalX = Math.max(0, viewportWidth - menuRect.width);
        }
    }

    // Aplicar posición final y mostrar
    Object.assign(el.style, {
        top: finalY + "px",
        left: finalX + "px",
        visibility: "visible",
    });

    current = el;

    setTimeout(() => {
        document.addEventListener("click", closeMenu);
        document.addEventListener("scroll", closeMenu, true);
        window.addEventListener("resize", closeMenu);
    }, 0);

    return el;
}

/**
 * Builder genérico: pásale items [{label, icon, danger, onClick}] y opcional headerHtml
 * Soporta items de tipo 'separator' para añadir líneas divisoras
 */
export function openActionsMenu(x, y, { headerHtml = "", items = [] }) {
    const html = `
    <div class="ctx-menu-container">
      ${headerHtml ? `<div class="ctx-menu-header">${headerHtml}</div>` : ""}
      ${items
          .map(
              (it, i) => {
                  // Si es separador, renderizar una línea divisora
                  if (it.type === 'separator') {
                      return `<div class="ctx-menu-separator" style="height:1px; background:#e5e7eb; margin:4px 0;"></div>`;
                  }
                  return `
        <button class="ctx-menu-item${
            it.danger ? " ctx-menu-danger" : ""
        }${
            it.disabled ? " ctx-menu-disabled" : ""
        }" data-idx="${i}" ${it.disabled ? 'disabled' : ''}>
          ${it.icon ? `<span class="ctx-menu-icon">${it.icon}</span>` : ""}
          <span class="ctx-menu-label">${it.label}</span>
        </button>
      `;
              }
          )
          .join("")}
    </div>
  `;
    const el = openMenuAt(x, y, html);

    el.querySelectorAll(".ctx-menu-item").forEach((btn) => {
        const idx = parseInt(btn.dataset.idx, 10);
        const item = items[idx];

        btn.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();

            console.log("[baseMenu] Click en item:", item.label, "disabled:", item.disabled);

            if (item.disabled) {
                console.log("[baseMenu] Item deshabilitado, ignorando");
                return;
            }

            const action = item.onClick;
            console.log("[baseMenu] Ejecutando acción...");

            closeMenu(); // Cerrar el menú primero

            try {
                await action?.(); // Ejecutar la acción
                console.log("[baseMenu] Acción completada");
            } catch (err) {
                console.error("[baseMenu] Error en acción:", err);
            }
        });
    });
    return el;
}
