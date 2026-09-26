import { useState } from "react";
import { Link } from "react-router-dom";
import { money } from "../../api";
import { useData, Notice, Panel, Field, Table } from "../../components/DataUI";
export default function SearchParking() {
  const state = useData("/lots");
  const [query, setQuery] = useState("");
  const rows = (state.data || []).filter((r) =>
    `${r.name} ${r.location} ${r.type}`
      .toLowerCase()
      .includes(query.toLowerCase()),
  );
  return (
    <Panel title="Find parking">
      <Notice {...state} />
      <Field
        label="Search by name, location or type"
        value={query}
        onChange={(e) => setQuery(e.target.value)}
      />
      <p>
        Availability below is for now. Choose your date and time when booking.
      </p>
      <Table
        rows={rows}
        columns={[
          { key: "name", label: "Lot" },
          { key: "location", label: "Location" },
          { key: "available_spots", label: "Free now" },
          { key: "total_spots", label: "Spaces" },
          {
            key: "hourly_rate",
            label: "Hourly rate",
            render: (r) => money(r.hourly_rate),
          },
        ]}
        actions={(r) => (
          <Link
            className="btn btn-primary btn-sm"
            to={`/driver/details/${r.id}`}
          >
            View and reserve
          </Link>
        )}
      />
    </Panel>
  );
}
