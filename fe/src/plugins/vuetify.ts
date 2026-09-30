import "@mdi/font/css/materialdesignicons.css";
import "vuetify/styles";
import { createVuetify } from "vuetify";

export default createVuetify({
  theme: {
    defaultTheme: "workspace",
    themes: {
      workspace: {
        dark: false,
        colors: {
          primary: "#244c3d",
          secondary: "#bd8356",
          background: "#f5f6f3",
          surface: "#ffffff",
          success: "#2d6b4c",
          error: "#b73737",
        },
      },
    },
  },
  defaults: {
    VBtn: {
      rounded: "lg",
      elevation: 0,
      style: "text-transform: none; letter-spacing: normal;",
    },
    VTextField: {
      variant: "outlined",
      density: "comfortable",
      hideDetails: "auto",
    },
    VSelect: {
      variant: "outlined",
      density: "comfortable",
      hideDetails: "auto",
    },
    VTextarea: { variant: "outlined", hideDetails: "auto" },
    VCard: { rounded: "xl", elevation: 0 },
  },
});
