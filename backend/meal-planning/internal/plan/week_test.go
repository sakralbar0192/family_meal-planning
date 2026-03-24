package plan

import (
	"testing"
	"time"
)

func TestWeekRangeWednesday(t *testing.T) {
	anchor, _ := time.Parse("2006-01-02", "2026-03-04") // Wednesday
	start, end := WeekRange(anchor)
	if DateString(start) != "2026-03-02" {
		t.Fatalf("weekStart %v", DateString(start))
	}
	if DateString(end) != "2026-03-08" {
		t.Fatalf("weekEnd %v", DateString(end))
	}
}
