const ngrok = require("@ngrok/ngrok");

const port = Number(process.env.NGROK_PORT || 80);
const authtoken = process.env.NGROK_AUTHTOKEN || "";

if (!authtoken) {
  console.error("NGROK_AUTHTOKEN не задан.");
  console.error("1) Открой https://dashboard.ngrok.com/get-started/your-authtoken");
  console.error("2) Выполни в PowerShell:");
  console.error("   setx NGROK_AUTHTOKEN \"твой_токен\"");
  console.error("3) Перезапусти терминал и снова запусти npm run ngrok");
  process.exit(1);
}

(async () => {
  try {
    const listener = await ngrok.forward({
      addr: port,
      authtoken,
    });

    console.log("ngrok tunnel started");
    console.log("Public URL:", listener.url());
    console.log(`Local target: http://localhost:${port}`);
    console.log("Keep this terminal open while using Bitrix24 app.");
  } catch (error) {
    console.error("Не удалось запустить ngrok:", String(error));
    process.exit(1);
  }
})();

process.stdin.resume();
