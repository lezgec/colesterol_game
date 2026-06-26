(function () {
    const paths = {
        activity: "<path d='M4 13h4l2-7 4 12 2-5h4'/>",
        analytics: "<path d='M4 19V5'/><path d='M4 19h16'/><path d='M8 16v-5'/><path d='M12 16V8'/><path d='M16 16v-9'/>",
        brain: "<path d='M9 4a3 3 0 0 0-3 3v1a3 3 0 0 0-2 2.8A3.2 3.2 0 0 0 7.2 14H9'/><path d='M15 4a3 3 0 0 1 3 3v1a3 3 0 0 1 2 2.8A3.2 3.2 0 0 1 16.8 14H15'/><path d='M9 4v16'/><path d='M15 4v16'/><path d='M9 10h6'/><path d='M9 15h6'/>",
        calendar: "<path d='M7 3v4'/><path d='M17 3v4'/><rect x='4' y='5' width='16' height='15' rx='3'/><path d='M4 10h16'/>",
        check: "<path d='m5 12 4 4L19 6'/>",
        clock: "<circle cx='12' cy='12' r='9'/><path d='M12 7v5l3 2'/>",
        download: "<path d='M12 3v11'/><path d='m7 10 5 5 5-5'/><path d='M5 20h14'/>",
        edit: "<path d='M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17v3Z'/><path d='m14 7 3 3'/>",
        file: "<path d='M7 3h7l4 4v14H7z'/><path d='M14 3v5h5'/><path d='M9 13h6'/><path d='M9 17h6'/>",
        gamepad: "<path d='M8 13h.01'/><path d='M6 16h.01'/><path d='M10 16h.01'/><path d='M8 18h.01'/><path d='M16 15h.01'/><path d='M18 17h.01'/><path d='M7 10h10a5 5 0 0 1 4.8 6.4l-.6 2.1a2.2 2.2 0 0 1-3.8.8L15 16H9l-2.4 3.3a2.2 2.2 0 0 1-3.8-.8l-.6-2.1A5 5 0 0 1 7 10Z'/>",
        heart: "<path d='M12 20s-7-4.4-9-9a4.7 4.7 0 0 1 8-5 4.7 4.7 0 0 1 8 5c-2 4.6-9 9-9 9Z'/>",
        home: "<path d='m3 11 9-8 9 8'/><path d='M5 10v10h14V10'/><path d='M9 20v-6h6v6'/>",
        leaf: "<path d='M5 19c8 0 14-6 14-14-8 0-14 6-14 14Z'/><path d='M5 19 19 5'/>",
        medal: "<path d='m8 3 4 7 4-7'/><circle cx='12' cy='15' r='5'/><path d='m10.5 15 1 1 2-2'/>",
        moon: "<path d='M20 14.5A8 8 0 0 1 9.5 4 7 7 0 1 0 20 14.5Z'/>",
        pause: "<path d='M8 5v14'/><path d='M16 5v14'/>",
        rocket: "<path d='M5 15c-1 1.5-1.5 3-1.5 5 2 0 3.5-.5 5-1.5'/><path d='M15 4c3 0 5 0 5 0s0 2-1 5c-1.2 3.8-4.5 7-8 8l-4-4c1-3.5 4.2-6.8 8-9Z'/><circle cx='15' cy='9' r='2'/>",
        school: "<path d='m3 10 9-5 9 5-9 5-9-5Z'/><path d='M7 12v5c3 2 7 2 10 0v-5'/><path d='M21 10v6'/>",
        star: "<path d='m12 3 2.6 5.5 6 .9-4.3 4.3 1 6-5.3-2.8-5.3 2.8 1-6-4.3-4.3 6-.9L12 3Z'/>",
        sun: "<circle cx='12' cy='12' r='4'/><path d='M12 2v2'/><path d='M12 20v2'/><path d='m4.9 4.9 1.4 1.4'/><path d='m17.7 17.7 1.4 1.4'/><path d='M2 12h2'/><path d='M20 12h2'/><path d='m4.9 19.1 1.4-1.4'/><path d='m17.7 6.3 1.4-1.4'/>",
        target: "<circle cx='12' cy='12' r='8'/><circle cx='12' cy='12' r='3'/><path d='M12 2v3'/><path d='M12 19v3'/><path d='M2 12h3'/><path d='M19 12h3'/>",
        trophy: "<path d='M8 4h8v4a4 4 0 0 1-8 0V4Z'/><path d='M8 6H4v2a4 4 0 0 0 4 4'/><path d='M16 6h4v2a4 4 0 0 1-4 4'/><path d='M12 12v5'/><path d='M8 21h8'/><path d='M9 17h6'/>",
        users: "<path d='M16 11a4 4 0 1 0-8 0'/><path d='M4 20a8 8 0 0 1 16 0'/><path d='M18 8a3 3 0 0 1 3 3'/><path d='M3 11a3 3 0 0 1 3-3'/>",
        x: "<path d='M6 6l12 12'/><path d='M18 6 6 18'/>",
        zap: "<path d='M13 2 4 14h7l-1 8 9-12h-7l1-8Z'/>"
    };

    window.uiIcon = function uiIcon(name, className = "ui-icon") {
        const key = paths[name] ? name : "star";
        return `<svg class="${className} ui-icon-${key}" viewBox="0 0 24 24" aria-hidden="true" focusable="false">${paths[key]}</svg>`;
    };
})();
