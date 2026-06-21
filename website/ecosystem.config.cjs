module.exports = {
  apps: [
    {
      name: "royal-kings-website",
      cwd: __dirname,
      script: "npm",
      args: "start -- -p 3001",
      env: {
        PORT: "3001",
        WEBSITE_BASE_PATH: process.env.WEBSITE_BASE_PATH || "/website",
        NEXT_PUBLIC_BASE_PATH: process.env.WEBSITE_BASE_PATH || "/website",
        NEXT_PUBLIC_API_URL:
          process.env.NEXT_PUBLIC_API_URL || "https://erp.royalkingsschools.sc.ke/api",
        NEXT_PUBLIC_ERP_LOGIN_URL:
          process.env.NEXT_PUBLIC_ERP_LOGIN_URL || "https://erp.royalkingsschools.sc.ke/login",
      },
    },
  ],
};
