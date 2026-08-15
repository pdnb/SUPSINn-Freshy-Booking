/* Shared Tailwind theme — brand-spec tokens (MRSU Freshy Booking) */
tailwind.config = {
  theme: {
    extend: {
      colors: {
        bg: "oklch(98% 0.004 250)",
        surface: "oklch(100% 0 0)",
        fg: "oklch(24% 0.02 255)",
        muted: "oklch(48% 0.014 255)",
        border: "oklch(88% 0.01 250)",
        accent: {
          DEFAULT: "oklch(48% 0.13 255)",
          press: "oklch(40% 0.13 255)",
          fg: "oklch(99% 0.01 255)",
        },
        brand: {
          DEFAULT: "oklch(48% 0.13 255)",
          press: "oklch(40% 0.13 255)",
          fg: "oklch(99% 0.01 255)",
        },
        success: "oklch(52% 0.14 145)",
        warn: "oklch(62% 0.14 75)",
        danger: "oklch(42% 0.16 20)",
        stage: "oklch(93% 0.008 250)",
      },
      fontFamily: {
        sans: ["Anuphan", "Sarabun", "system-ui", "sans-serif"],
      },
      maxWidth: {
        phone: "390px",
        app: "48rem",
      },
      screens: {
        // Align md with common iPad portrait width
        md: "768px",
      },
      minHeight: {
        phone: "844px",
      },
      borderRadius: {
        phone: "28px",
      },
      boxShadow: {
        phone: "0 18px 48px oklch(20% 0.02 240 / 0.12)",
      },
      spacing: {
        tabbar: "58px",
      },
    },
  },
};
