package plan

import (
	"time"
)

var SlotCodes = []string{
	"BREAKFAST",
	"SECOND_BREAKFAST",
	"LUNCH",
	"SNACK",
	"DINNER",
	"LATE_DINNER",
}

// WeekRange returns inclusive Monday..Sunday dates in UTC (YYYY-MM-DD).
func WeekRange(anchorDate time.Time) (weekStart, weekEnd time.Time) {
	t := anchorDate.UTC()
	y, m, d := t.Date()
	mid := time.Date(y, m, d, 0, 0, 0, 0, time.UTC)
	wd := int(mid.Weekday())
	if wd == 0 {
		wd = 7
	}
	daysFromMonday := wd - 1
	start := mid.AddDate(0, 0, -daysFromMonday)
	end := start.AddDate(0, 0, 6)
	return start, end
}

func DateString(t time.Time) string {
	return t.UTC().Format("2006-01-02")
}

func ParseDate(s string) (time.Time, error) {
	return time.ParseInLocation("2006-01-02", s, time.UTC)
}
