/** @type {import('tailwindcss').Config} */
export default {
    content: ["./templates/**/*.{html,twig}", "./assets/**/*.{js,jsx,ts,tsx}"],
    theme: {
        extend: {
            colors: {
                // Obsidian Liquid Glass Palette
                obsidian: {
                    50: "#f8f9fa",
                    100: "#f1f1f3",
                    200: "#e8e9eb",
                    300: "#d8dade",
                    400: "#b8bcc5",
                    500: "#94a3b8",
                    600: "#64748b",
                    700: "#475569",
                    800: "#1e293b",
                    900: "#0f172a",
                    950: "#07080f", // Deep charcoal base
                },
                luxury: {
                    teal: "#10b981",
                    violet: "#8b5cf6",
                    gold: "#f59e0b",
                    red: "#ef4444",
                    cyan: "#06b6d4",
                    amber: "#fbbf24",
                    emerald: "#10b981",
                },
                glass: {
                    "white-5": "rgba(255, 255, 255, 0.05)",
                    "white-10": "rgba(255, 255, 255, 0.1)",
                    "white-15": "rgba(255, 255, 255, 0.15)",
                    "white-20": "rgba(255, 255, 255, 0.2)",
                },
            },
            fontFamily: {
                jakarta: [
                    "Plus Jakarta Sans",
                    "system-ui",
                    "-apple-system",
                    "sans-serif",
                ],
                playfair: ["Playfair Display", "serif"],
            },
            backdropBlur: {
                glass: "20px",
                "glass-lg": "30px",
            },
            boxShadow: {
                glass: "0 8px 32px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.05)",
                "glass-lg":
                    "0 20px 50px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.05)",
                "glow-teal": "0 0 20px rgba(16, 185, 129, 0.25)",
                "glow-violet": "0 0 20px rgba(139, 92, 246, 0.25)",
                "glow-gold": "0 0 20px rgba(245, 158, 11, 0.25)",
                "glow-cyan": "0 0 20px rgba(6, 182, 212, 0.25)",
            },
            animation: {
                "fade-in": "fadeIn 0.6s ease-out",
                "slide-up": "slideUp 0.5s ease-out",
                float: "float 3s ease-in-out infinite",
                "pulse-glow": "pulseGlow 2s ease-in-out infinite",
            },
            keyframes: {
                fadeIn: {
                    from: { opacity: "0" },
                    to: { opacity: "1" },
                },
                slideUp: {
                    from: { opacity: "0", transform: "translateY(20px)" },
                    to: { opacity: "1", transform: "translateY(0)" },
                },
                float: {
                    "0%, 100%": { transform: "translateY(0px)" },
                    "50%": { transform: "translateY(-10px)" },
                },
                pulseGlow: {
                    "0%, 100%": { opacity: "1" },
                    "50%": { opacity: "0.7" },
                },
            },
            transitionTimingFunction: {
                luxury: "cubic-bezier(0.23, 1, 0.32, 1)",
            },
            borderRadius: {
                glass: "20px",
                "glass-lg": "30px",
            },
            spacing: {
                xs: "4px",
                sm: "8px",
                md: "12px",
                lg: "24px",
                xl: "32px",
            },
        },
    },
    plugins: [],
};
