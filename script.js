document.addEventListener("DOMContentLoaded", () => {
    const descriptions = {
        workflow: "Automates multi-step workflows, approvals, reminders, and cross-tool processes. Best for repetitive operations.",
        agents: "AI-powered assistants that support tasks, decisions, and communication with optional human review.",
        data: "Automates document creation, summaries, reports, spreadsheets, and knowledge extraction.",
        communication: "Automates scheduling, meetings, follow-ups, and team coordination.",
        industry: "Tailored AI solutions built for specific industries and operational needs.",
        custom: "Fully custom AI automation solutions. If it can be automated, we can build it."
    };

    const select = document.getElementById("serviceSelect");
    const box = document.getElementById("serviceDescription");

    select.addEventListener("change", () => {
        if (descriptions[select.value]) {
            box.innerText = descriptions[select.value];
            box.style.display = "block";
        } else {
            box.style.display = "none";
        }
    });
});
