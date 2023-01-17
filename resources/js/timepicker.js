class Timepicker {
    static date = new Date();
    static htmlElementId = "week-time-picker";
    static daysOfWeek = {
        1: "Monday",
        2: "Tuesday",
        3: "Wednesday",
        4: "Thursday",
        5: "Friday",
        6: "Saturday",
        7: "Sunday",
    }

    static init() {
        document.getElementById(this.htmlElementId).addEventListener('click', event => {
            if (event.target.classList.contains('hour')) {
                document.getElementById('date').value = event.target.dataset.date;
            }
            console.log(event.target);
        });
    }

    static async showWeekHTML() {
        const xhttp = new XMLHttpRequest();
        xhttp.onload = function () {
            let tableHTML = '<table>\n' +
                '<thead>\n' +
                '    <th class="p-6"></th>\n' +
                '    <th class="p-6">Monday</th>\n' +
                '    <th class="p-6">Tuesday</th>\n' +
                '    <th class="p-6">Wednesday</th>\n' +
                '    <th class="p-6">Thursday</th>\n' +
                '    <th class="p-6">Friday</th>\n' +
                '    <th class="p-6">Saturday</th>\n' +
                '    <th class="p-6">Sunday</th>\n' +
                '</thead>\n' +
                '<tbody>\n';

            let schedule = JSON.parse(this.responseText);
            for (const [hour, values] of Object.entries(schedule)) {
                tableHTML += '<tr><td class="text-center">' + hour + '</td>';
                for (let i = 1; i <= 7; i++) {
                    tableHTML += '<td class="text-center"><div class="hour ' + values[i]['status'] + '" data-date="' + values[i]['date'] + '"></div></td>';
                }
                tableHTML += '</tr>';
            }

            tableHTML += '</tbody></table>';

            let html = document.getElementById(Timepicker.htmlElementId);
            html.innerHTML = tableHTML;
        }
        xhttp.open("GET", "/api/schedule?date=" + this.date.toDateString(), true);
        xhttp.send();
    }
}

Timepicker.init();
Timepicker.showWeekHTML();
