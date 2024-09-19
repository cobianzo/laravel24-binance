import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: "#CCC",
                accent: "#F59E0B", // El color de acento original
                secondary: "#065ee2", // Un poco más oscuro que el acento para contraste
                danger: "#D97706", // Un tono más profundo y cálido para las advertencias
                success: "#FFD166",
                dark: "#000000",
                light: "#F8F9FA",
            },
        },
    },

    plugins: [forms],
};
