// Theme Toggle Functionality
class ThemeManager {
  constructor() {
    this.currentTheme = this.getStoredTheme() || "light"
    this.init()
  }

  init() {
    this.applyTheme(this.currentTheme)
    this.createToggleButton()
    this.bindEvents()
  }

  getStoredTheme() {
    return localStorage.getItem("theme")
  }

  setStoredTheme(theme) {
    localStorage.setItem("theme", theme)
  }

  applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme)
    this.currentTheme = theme
    this.setStoredTheme(theme)
    this.updateToggleButton()
  }

  toggleTheme() {
    const newTheme = this.currentTheme === "light" ? "dark" : "light"
    this.applyTheme(newTheme)

    // Add a nice transition effect
    document.body.style.transition = "all 0.3s ease"
    setTimeout(() => {
      document.body.style.transition = ""
    }, 300)
  }

  createToggleButton() {
    const toggleHTML = `
            <div class="theme-toggle" id="themeToggle">
                <span class="theme-toggle-label">Theme</span>
                <div class="theme-toggle-switch">
                    <div class="theme-toggle-slider">
                        <i class="fas fa-sun" id="lightIcon"></i>
                        <i class="fas fa-moon" id="darkIcon" style="display: none;"></i>
                    </div>
                </div>
            </div>
        `

    // Find a suitable container (header, navbar, etc.)
    const container =
      document.querySelector(".theme-toggle-container") ||
      document.querySelector("header") ||
      document.querySelector(".navbar") ||
      document.body

    if (container) {
      const toggleElement = document.createElement("div")
      toggleElement.innerHTML = toggleHTML
      toggleElement.style.position = "fixed"
      toggleElement.style.top = "20px"
      toggleElement.style.right = "20px"
      toggleElement.style.zIndex = "9999"

      container.appendChild(toggleElement)
    }
  }

  updateToggleButton() {
    const lightIcon = document.getElementById("lightIcon")
    const darkIcon = document.getElementById("darkIcon")

    if (lightIcon && darkIcon) {
      if (this.currentTheme === "dark") {
        lightIcon.style.display = "none"
        darkIcon.style.display = "block"
      } else {
        lightIcon.style.display = "block"
        darkIcon.style.display = "none"
      }
    }
  }

  bindEvents() {
    document.addEventListener("click", (e) => {
      if (e.target.closest("#themeToggle")) {
        this.toggleTheme()
      }
    })

    // Keyboard shortcut (Ctrl/Cmd + Shift + T)
    document.addEventListener("keydown", (e) => {
      if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === "T") {
        e.preventDefault()
        this.toggleTheme()
      }
    })
  }

  // Method to manually set theme
  setTheme(theme) {
    if (theme === "light" || theme === "dark") {
      this.applyTheme(theme)
    }
  }

  // Method to get current theme
  getCurrentTheme() {
    return this.currentTheme
  }
}

// Initialize theme manager when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  window.themeManager = new ThemeManager()
})

// Auto-detect system preference
if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches && !localStorage.getItem("theme")) {
  document.documentElement.setAttribute("data-theme", "dark")
}

// Listen for system theme changes
if (window.matchMedia) {
  window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", (e) => {
    if (!localStorage.getItem("theme")) {
      const theme = e.matches ? "dark" : "light"
      document.documentElement.setAttribute("data-theme", theme)
    }
  })
}
