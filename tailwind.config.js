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
                secondary: "#F59E0B",
                accent: "#F59E0B",
                danger: "#F59E0B",
                success: "#F59E0B",
                dark: "#000000",
                light: "#F8F9FA",
            },
        },
    },

    plugins: [forms],
};
